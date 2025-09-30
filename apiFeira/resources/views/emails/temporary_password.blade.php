{{-- resources/views/emails/temporary_password.blade.php --}}

<!DOCTYPE html>
<html>
<head>
    <title>Sua Nova Senha Temporária</title>
</head>
<body>
    <h1>Redefinição de Senha</h1>
    
    <p>Olá,</p>
    
    <p>Sua nova senha é:</p>
    
    <h2 style="color: #1B5E20;">{{ $password }}</h2>
    
    <p>Caso deseje, faça login e altere sua senha.</p>
    
      
    <p>Atenciosamente,<br>Equipe de Suporte</p>
</body>
</html>