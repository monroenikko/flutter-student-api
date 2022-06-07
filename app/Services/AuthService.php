<?php
namespace App\Services\Auth;

use Exception;
use Carbon\Carbon;
use App\Models\User;
use App\Traits\ResponseApi;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\User\UserResource;

class AuthService
{
    use ResponseApi;
    protected $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function register($data){

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
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
            $creds = ['email' => $data['email'], 'password' => $data['password']];
            if (!Auth::attempt($creds)) {
                return $this->error('These credentials do not match our records.', Response::HTTP_UNAUTHORIZED);
            }
            $user = $this->model->whereEmail($data['email'])->firstOrFail();
            
            $token = $user->createToken('auth_token')->plainTextToken;
            $now = Carbon::now()->addMinutes(config('sanctum.expiration'));
            
            return $this->success(
                'You are successfully login. Welcome back ' . $user->full_name . '!',
                Response::HTTP_OK,
                [
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $now
                ],
            );
            // return response([
            //     'user' => $user,
            //     'token' => $token,
            // ],200);
        } catch (Exception $e) {
            dd($e);
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function user($data)
    {
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
            return $this->success('Data Successfully Fetched.', Response::HTTP_OK, [$res]);
        } catch (Exception $e) {
            return $this->error($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function checkForPermission($user, $data)
    {
        $permission = json_decode($user->role->permission);

        $hasPermission = false;
        if (!$permission) {
            return $this->error('No Permission', Response::HTTP_BAD_REQUEST);
        }
        foreach ($permission as $p) {
            if ($p->name == $data->path()) {
                if ($p->read) {
                    $hasPermission = true;
                }
            }
        }
        if ($hasPermission) {
            return 'welcome';
        }
        return $this->error('No Permission', Response::HTTP_BAD_REQUEST);
    }

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