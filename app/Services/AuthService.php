<?php
namespace App\Services;

use Exception;
use Carbon\Carbon;
use App\Models\{User, StudentInformation};
use Illuminate\Http\Response;
use App\Services\ClassRecordService;
use App\Http\Resources\User\UserResource;
use App\Traits\{ SchoolYear, ResponseApi };
use Illuminate\Auth\Events\{ Login, Logout };
use Illuminate\Support\Facades\{ Auth, Event };

class AuthService
{
    use ResponseApi, SchoolYear;

    protected $model, $class_record, $schoolYear;
    public function __construct(User $model, ClassRecordService $class_record)
    {
        $this->model = $model;
        $this->class_record = $class_record;
        $this->schoolYear = $this->activeSchoolYear();
    }

    private function classDetail()
    {
       return $this->class_record->hasClassDetail($this->schoolYear->id, $sem = null) ?? $this->class_record->hasClassDetail($this->schoolYear->id-1, $sem = null);
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
            $user = $this->model->with(['user'])->whereUsername($data['username'])->firstOrFail();

            $token = $user->createToken('auth_token')->plainTextToken;
            $now = Carbon::now()->addMinutes(config('sanctum.expiration'));
            $class_detail = $this->classDetail();
            $user['section'] = isset($class_detail) ? $class_detail->classDetail->section->section : 'none';
            $user['grade_level'] = isset($class_detail) ? $class_detail->classDetail->section->grade_level : 'none';
            $user['school_year'] = $this->schoolYear->school_year;

            Event::dispatch(new Login('api', $user, false)); //fire the login event
            
            return $this->success(
                'You are successfully login. Welcome back ' . $user->user->full_name . '!',
                Response::HTTP_OK,
                [
                    'user' => new UserResource($user),
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $now
                ]
            );
        } catch (Exception $e) {
            dd($e);
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function userData($data)
    {
        $class_detail = $this->classDetail();
        $data['section'] = isset($class_detail) ? $class_detail->classDetail->section->section : 'none';
        $data['grade_level'] = isset($class_detail) ? $class_detail->classDetail->section->grade_level : 'none';
        $data['school_year'] = $this->schoolYear->school_year;
        $user = new UserResource($data->user());
        return $this->success('Successfully fetch', Response::HTTP_OK, ['user' => $user]);
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
            \Log::error($e);
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
    
}
