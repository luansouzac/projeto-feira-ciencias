<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class UserAuthController extends Controller
{
    public function register(Request $request)
    {
        $registerUserData = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:usuarios,email',
            'password' => 'required|min:6',
            'id_tipo_usuario' => 'required|integer|exists:tipo_usuarios,id_tipo_usuario',
            'id_matricula' => 'required|string|max:100',
        ]);

        $user = Usuario::create([
            'nome' => $registerUserData['name'],
            'email' => $registerUserData['email'],
            'senha_hash' => Hash::make($registerUserData['password']),
            'id_tipo_usuario' => $registerUserData['id_tipo_usuario'],
            'id_matricula' => $registerUserData['id_matricula'],    
        ]);

        return response()->json([
            'message' => 'Usuário criado com sucesso',
        ]);
    }

    public function login(Request $request)
    {
        $loginUserData = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|min:6'
        ]);

        $user = Usuario::where('email', $loginUserData['email'])->first();

        if (!$user || !Hash::check($loginUserData['password'], $user->senha_hash)) {
            return response()->json([
                'message' => 'Credenciais inválidas'
            ], 401);
        }

        $token = $user->createToken($user->nome . '-AuthToken')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'user'=> $user
        ]);
    }

    // public function logout()
    // {
    //     auth()->user()->tokens()->delete();

    //     return response()->json([
    //         "message" => "logged out"
    //     ]);
    // }
}