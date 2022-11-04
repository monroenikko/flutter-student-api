<?php
namespace App\Services\Auth;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Traits\{SchoolYear, ResponseApi};
use Illuminate\Http\Response;
use App\Services\ClassRecordService;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\User\UserResource;

class AuthService
{
    use ResponseApi, SchoolYear;

    protected $model, $class_record;
    public function __construct(User $model, ClassRecordService $class_record)
    {
        $this->model = $model;
        $this->class_record = $class_record;
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
            ],
        );
    }

    public function login($data)
    {
        try {
            $creds = ['username' => $data['username'], 'password' => $data['password']];
            if (!Auth::attempt($creds)) {
                return $this->error('These credentials do not match our records.', Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->model->with(['user'])->whereUsername($data['username'])->firstOrFail();

            $token = $user->createToken('auth_token')->plainTextToken;
            $now = Carbon::now()->addMinutes(config('sanctum.expiration'));
            $school_year = $this->activeSchoolYear();
            $class_detail = $this->class_record->hasClassDetail($school_year->id, $sem = null);
            $user['section'] = $class_detail->classDetail->section->section;
            $user['grade_level'] = $class_detail->classDetail->section->grade_level;
            $user['school_year'] = $school_year->school_year;
            // dd($user);
            return $this->success(
                'You are successfully login. Welcome back ' . $user->user->full_name . '!',
                Response::HTTP_OK,
                [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $now
                ],
            );
        } catch (Exception $e) {
            // dd($e);
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function userData($data)
    {
        $school_year = $this->activeSchoolYear();
        $class_detail = $this->class_record->hasClassDetail($school_year->id, $sem = null);
        $data['section'] = $class_detail->classDetail->section->section;
        $data['grade_level'] = $class_detail->classDetail->section->grade_level;
        $data['school_year'] = $school_year->school_year;
        $user = new UserResource($data->user());
        return $this->success('Successfully fetch', Response::HTTP_OK, ['user' => $user]);
    }

    public function update($data, $image)
    {
        try {
            $res = auth()->user()->update([
                'name' => $data['name'],
                'image' => $image
            ]);
            return $this->success('Data Successfully Updated.', Response::HTTP_OK, [$res]);
        } catch (Exception $e) {
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
                ['token' => $data->user()->createToken('api')->plainTextToken],
            );
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
