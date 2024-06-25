<?php
include('conexao.php');

session_start();

$error_message = '';

if (isset($_POST['usuario']) && isset($_POST['senha'])) {
    if (strlen($_POST['usuario']) == 0) {
        $error_message = "Preencha seu usuário";
    } elseif (strlen($_POST['senha']) == 0) {
        $error_message = "Preencha sua senha";
    } else {
        $usuario = $mysqli->real_escape_string($_POST['usuario']);
        $senha = $mysqli->real_escape_string($_POST['senha']);

        $sql_code = "SELECT * FROM clientes WHERE nome = '$usuario' AND senha = '$senha'";
        $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: " . $mysqli->error);

        $quantidade = $sql_query->num_rows; 

        if ($quantidade == 1) {
            $usuarios = $sql_query->fetch_assoc();

            $_SESSION['id_cliente'] = $usuarios['id_cliente'];
            $_SESSION['nome'] = $usuarios['nome'];

            $sql_code_historico = "INSERT INTO historico_login(id_cliente, data_hora_login)
                        VALUES('{$_SESSION['id_cliente']}', NOW())";
            $sql_query2 = $mysqli->query($sql_code_historico) or die("Falha na execução do código SQL: " . $mysqli->error);

            // Registrar o evento de login na tabela log_eventos
            $evento = "Cliente {$_SESSION['nome']} realizou login em " . date('Y-m-d H:i:s');
            $sql_code_log = "INSERT INTO log_eventos(evento) VALUES('$evento')";
            $mysqli->query($sql_code_log) or die("Falha na execução do código SQL: " . $mysqli->error);

            if ($usuario === 'admin') {
                header("Location: painel.php"); // Página do admin
            } else {
                header("Location: painel_cliente.php"); // Página do cliente
            }
            exit;
        } else {
            $error_message = "Usuário ou senha incorretos";
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
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        form {
            max-width: 400px;
            width: 100%;
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #007bff;
            margin-bottom: 30px;
            font-size: 24px;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        label.text {
            text-align: center;
            font-size: 24px;
            color: #007bff;
            margin-bottom: 30px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
        }

        button[type="submit"] {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 15px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
            font-size: 16px;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        p {
            margin: 0;
        }

        .message {
            color: red;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <form action="" method="POST">
        <label class="text">Acesse sua conta</label>

        <?php if($error_message): ?>
            <p class="message"><?php echo $error_message; ?></p>
        <?php endif; ?>

        <p>
            <label for="usuario">Usuário</label>
            <input type="text" id="usuario" name="usuario">
        </p>

        <p>
            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha">
        </p>

        <p>
            <button type="submit">Entrar</button>
        </p>
    </form>
</body>
</html>
