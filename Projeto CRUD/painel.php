<?php
    include('protect.php');
    include('conexao.php');

    if( isset($_POST['nome']) || isset($_POST['email']) || isset($_POST['senha'])) 
    {
         if(strlen($_POST['nome']) == 0) {
            echo "Preencha o nome";
        } else if(strlen($_POST['email']) == 0) {
            echo "Preencha o email";
        } else if(strlen($_POST['senha']) == 0 ) {
            echo "Preencha a senha";
        } else {

            $nome = $mysqli->real_escape_string($_POST['nome']);
            $email = $mysqli->real_escape_string($_POST['email']);
            $senha = $mysqli->real_escape_string($_POST['senha']);

            $sql_code = "INSERT INTO clientes(nome,email,senha,data_cadastro) VALUES
                        ('$nome', '$email', '$senha', '2023-01-01')";
            echo $sql_code;
            $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: " . $mysqli->error);
    
        }
    }

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>
</head>
<body>
    <h1>Bem vindo ao Painel, <?php echo $_SESSION['nome']; ?></h1>

    <form action="" method="POST">

    <p>

        <label for="">Nome do cliente:</label>
        <input type="text" name="nome">

        <label for="">Email do Cliente:</label>
        <input type="email" name="email">

        <label for="">Senha:</label>
        <input type="password" name="senha">

    </p>

    <p>
        <button type="submit">Enviar</button>
    </p>

    </form>

    <p>
        <a href="logout.php">Sair</a>
    </p>
</body>
</html>