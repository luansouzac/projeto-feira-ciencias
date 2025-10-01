<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PasswordResetController extends Controller
{
    public function generateAndSend(Request $request)
    {
        // 1. Validação do E-mail
        $request->validate(['email' => 'required|email']);
        $email = $request->email;

        // 2. Busca e Verificação de Usuário
        $user = Usuario::where('email', $email)->first();

        // **Prática de Segurança:** Retorne sucesso mesmo se o usuário não for encontrado.
        if (!$user) {
            return response()->json([
                'message' => 'Se o e-mail estiver cadastrado em nosso sistema, uma nova senha temporária será enviada.'
            ], 202);
        }

        // 3. Geração da Nova Senha (CLARA)
        // Usamos Str::random() para uma senha alfanumérica de 12 caracteres.
        // **OPCIONAL:** Para mais segurança, crie uma função customizada mais robusta.
        $newClearPassword = Str::random(8);

        // 4. Hashing da Senha e Atualização no Banco de Dados
        $user->senha_hash = Hash::make($newClearPassword);
        
        // OPCIONAL: Adicionar um flag para forçar o usuário a trocar a senha no próximo login.
        // $user->force_password_change = true; 
        
        $user->save();

        // 5. Envio do E-mail
        try {
            // Usa o sistema de Mailable do Laravel para enviar a nova senha.
            Mail::to($user->email)->send(new TemporaryPasswordMail($newClearPassword));
            
        } catch (\Exception $e) {
            // Tratar falha no envio de e-mail (ex: logar o erro).
            Log::error("Falha no envio de e-mail de nova senha para {$user->email}: " . $e->getMessage());
            // A API deve retornar sucesso, pois a senha foi alterada no DB.
        }

        // 6. Resposta Final
        return response()->json([
            'message' => 'Uma nova senha temporária foi enviada para o seu e-mail.'
        ], 201);
    }
}
