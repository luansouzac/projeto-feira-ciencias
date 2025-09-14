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

    public function show($id)
    {
        $user = Usuario::find($id);
        if (!$user) {
            return response()->json(["message" => "Usuário não encontrado"], 404);
        }
        return response()->json(["user" => $user]);
    }

    public function update(Request $request, $id)
    {
        $user = Usuario::find($id);
        if (!$user) {
            return response()->json(["message" => "Usuário não encontrado"], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|email|unique:usuarios,email,' . $id . ',id_usuario',
            'cpf' => 'required|string|max:14|unique:usuarios,cpf,' . $id . ',id_usuario',
            'telefone' => 'required|string|max:20',
            'instituicao' => 'required|string|max:255',
            'curso' => 'required|string|max:255',
            'id_tipo_usuario' => 'required|integer|exists:tipo_usuarios,id_tipo_usuario',
            'id_matricula' => 'required|string|max:100',
        ]);

        $user->nome = $validatedData['name'];
        $user->email = $validatedData['email'];
        $user->cpf = $validatedData['cpf'];
        $user->telefone = $validatedData['telefone'];
        $user->instituicao = $validatedData['instituicao'];
        $user->curso = $validatedData['curso'];
        $user->id_tipo_usuario = $validatedData['id_tipo_usuario'];
        $user->id_matricula = $validatedData['id_matricula'];
        $user->save();

        return response()->json(["message" => "Perfil atualizado com sucesso!", "user" => $user]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json([
            "message" => "logged out"
        ]);
    }
}
