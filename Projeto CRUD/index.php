<?php
include('conexao.php');

if(isset($_POST['usuario']) || isset($_POST['senha'])) {

    if(strlen($_POST['usuario']) == 0) {
        echo "Preencha seu usuario";
    } else if(strlen($_POST['senha']) == 0) {
        echo "Preencha sua senha";
    } else {

        $usuario = $mysqli->real_escape_string($_POST['usuario']);
        $senha = $mysqli->real_escape_string($_POST['senha']);

        $sql_code = "SELECT * FROM clientes WHERE nome = '$usuario' AND senha = '$senha'";
        $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: " . $mysqli->error);


        $quantidade = $sql_query->num_rows;

        if($quantidade == 1) {
            
            $usuarios = $sql_query->fetch_assoc();

            if(!isset($_SESSION)) {
                session_start();
            }

            $_SESSION['id_cliente'] = $usuarios['id_cliente'];
            $_SESSION['nome'] = $usuarios['nome'];

            header("Location: painel.php");

        } 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <h1>Acesse sua conta</h1>

    <form action="" method="POST">
        <p>
            <label for="">Usuario</label>
            <input type="text" name="usuario">
        </p>

        <p>
            <label for="">Senha</label>
            <input type="password" name="senha">

        </p>

        <p>
            <button type="submit">Entrar</button>

        </p>
    </form>
    
</body>
</html>