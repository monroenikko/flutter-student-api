<?php
namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\{User, StudentInformation};
use Illuminate\Http\Response;
use App\Services\ClassRecordService;
use App\Http\Resources\User\UserResource;
use App\Traits\{ SchoolYear, ResponseApi, HasSiblingAccess };
use Illuminate\Auth\Events\{ Login, Logout };
use Illuminate\Support\Facades\{ Auth, Event, Log, Hash };

class AuthService
{
    use ResponseApi, SchoolYear, HasSiblingAccess;

    protected $model, $class_record;
    public function __construct(User $model, ClassRecordService $class_record)
    {
        $this->model = $model;
        $this->class_record = $class_record;
    }

    private function classDetail($schoolYear)
    {
        if (!$schoolYear) {
            return null;
        }

        return $this->class_record->hasClassDetail($schoolYear->id, null)
            ?? $this->class_record->hasClassDetail($schoolYear->id - 1, null);
    }

    public function register($data){

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'password' => bcrypt($data['password']),
        ]);

       return $this->success(
            'You are successfully registered.',
            Response::HTTP_OK,
            [
                'user' => $user,
                'token' => $user->createToken('secret')->plainTextToken
            ]
        );
    }

    public function login($data)
    {
        try {
            $creds = ['username' => $data['username'], 'password' => $data['password']];
            if (!Auth::attempt($creds)) {
                return $this->error('These credentials do not match our records.', Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->model->where('status', 1)->with('user')->whereUsername($data['username'])->first();
            if (!$user || !$user->user) {
                Auth::logout();

                return $this->error("Sorry, You don't have access, please reach our admin. Thank you", Response::HTTP_BAD_REQUEST);
            }

            $expiresIn = config('sanctum.expiration')
                ? Carbon::now()->addMinutes((int) config('sanctum.expiration'))
                : null;
            $schoolYear = $this->activeSchoolYear();
            $class_detail = $this->classDetail($schoolYear);
            $user['section'] = data_get($class_detail, 'classDetail.section.section', 'none');
            $user['grade_level'] = data_get($class_detail, 'classDetail.section.grade_level', 'none');
            $user['school_year'] = $schoolYear->school_year ?? 'none';
            $token = $user->createToken('auth_token')->plainTextToken;
            Event::dispatch(new Login('api', $user, false)); //fire the login event

            return $this->success(
                'You are successfully login. Welcome back ' . $user->user->full_name . '!',
                Response::HTTP_OK,
                [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $expiresIn
                ]
            );
        } catch (Exception $e) {

            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function userData($data)
    {
        $studentId = $data->get('student_id');
        $student = $studentId ? $this->getAuthorizedStudent($studentId) : null;

        $schoolYear = $this->activeSchoolYear();
        $class_detail = $this->classDetail($schoolYear);
        $section = data_get($class_detail, 'classDetail.section.section', 'none');
        $grade_level = data_get($class_detail, 'classDetail.section.grade_level', 'none');
        $school_year = $schoolYear->school_year ?? 'none';

        $data['section'] = $section;
        $data['grade_level'] = $grade_level;
        $data['school_year'] = $school_year;

        $authUser = $data->user();
        $playerId = $data->get('player_id');
        $this->syncSiblingSubscriptions($authUser, $playerId);

        if ($student) {
            $user = ($student->user_id ? User::with('user')->where('id', $student->user_id)->first() : null) ?? clone $authUser;
            $user->setRelation('user', $student);
        } else {
            $user = $authUser;
        }

        $user['section'] = $section;
        $user['grade_level'] = $grade_level;
        $user['school_year'] = $school_year;

        return $this->success('Successfully fetch', Response::HTTP_OK, ['user' => new UserResource($user)]);
    }

    public function update(array $data, $image)
    {
        try {
            $data['age'] = (int) $data['age'];
            $data['gender'] = (int) $data['gender'];
            if($image !== null)
            {
                $data['photo'] = $image;
            }
            $user = StudentInformation::where('user_id', Auth::user()->id)->first();
            $user->fill($data)->save();

            return $this->success('Data Successfully Updated.', Response::HTTP_OK, []);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    // public function checkForPermission($user, $data)
    // {
    //     $permission = json_decode($user->role->permission);

    //     $hasPermission = false;
    //     if (!$permission) {
    //         return $this->error('No Permission', Response::HTTP_BAD_REQUEST);
    //     }
    //     foreach ($permission as $p) {
    //         if ($p->name == $data->path()) {
    //             if ($p->read) {
    //                 $hasPermission = true;
    //             }
    //         }
    //     }
    //     if ($hasPermission) {
    //         return 'welcome';
    //     }
    //     return $this->error('No Permission', Response::HTTP_BAD_REQUEST);
    // }

    public function logout($data)
    {
        try {
            $data->user()->currentAccessToken()->delete();
            Event::dispatch(new Logout('api', $data->user(), false)); //fire the logout event
            return $this->success('Logged out successfully', Response::HTTP_OK, []);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function refresh($data)
    {
        try {
            $data->user()->tokens()->delete();
            return $this->success(
                'Successfully Refresh Token.',
                Response::HTTP_OK,
                ['token' => $data->user()->createToken('api')->plainTextToken]
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changePassword(array $data)
    {
        try {
            $user = Auth::user();
            if (!Hash::check($data['current_password'], $user->password)) {
                return $this->error('The provided current password does not match your password.', Response::HTTP_BAD_REQUEST);
            }

            if (Hash::check($data['new_password'], $user->password)) {
                return $this->error('The new password cannot be the same as your current password.', Response::HTTP_BAD_REQUEST);
            }

            $user->update([
                'password' => Hash::make($data['new_password']),
            ]);

            return $this->success('Password successfully changed.', Response::HTTP_OK, []);
        } catch (Exception $e) {
            Log::error($e);
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

}
