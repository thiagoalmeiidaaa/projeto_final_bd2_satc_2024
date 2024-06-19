<?php
    include('protect.php');
    include('conexao.php');
    if(isset($_POST['nome']) && isset($_POST['email']) && isset($_POST['senha']) && isset($_POST['operacao'])) 
    {
         if(strlen($_POST['nome']) == 0  ) {
            echo "Preencha o nome";
        } else if(strlen($_POST['email']) == 0) {
            echo "Preencha o email";
        } else if(strlen($_POST['senha']) == 0) {
            echo "Preencha a senha";
        }  
        else 
        {
            $id_cliente = $mysqli->real_escape_string($_POST['id_cliente']);
            $nome = $mysqli->real_escape_string($_POST['nome']);
            $email = $mysqli->real_escape_string($_POST['email']);
            $senha = $mysqli->real_escape_string($_POST['senha']);
            $operacao = $mysqli->real_escape_string($_POST['operacao']);
            echo $operacao;

            if($operacao == 'insert') 
            {
                $sql_code = "INSERT INTO clientes(nome,email,senha,data_cadastro) 
                            VALUES
                            ('$nome', '$email', '$senha', '2023-01-01')";

                $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: " . $mysqli->error);
                if($sql_query) {
                    echo "Operação '$operacao' executada com sucesso!";
                    // Redireciona para a mesma página para limpar o formulário
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                }

            } 
            else if($operacao == 'update') 
            {
                $sql_code = "UPDATE clientes 
                            SET nome='$nome', email='$email', senha='$senha' 
                            WHERE id_cliente='$id_cliente'";

                $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: " . $mysqli->error);

                if($sql_query) {
                    echo "Operação '$operacao' executada com sucesso!";
                    // Redireciona para a mesma página para limpar o formulário
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                }

            }
            else if($operacao == 'delete') 
            {
                $sql_code = "DELETE * 
                            FROM clientes 
                            WHERE id_cliente ='$id_cliente'";

                $sql_query = $mysqli->query($sql_code) or die("Falha na execução do código SQL: " . $mysqli->error);

                if($sql_query) {
                    echo "Operação '$operacao' executada com sucesso!";
                    // Redireciona para a mesma página para limpar o formulário
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                }
            }

        }
    } 
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>
    <script>
        function toggleFields() {
            const operacao = document.getElementById('operacao').value;
            const nomeField = document.getElementById('nomeField');
            const emailField = document.getElementById('emailField');
            const senhaField = document.getElementById('senhaField');
            const idClienteField = document.getElementById('idClienteField');

            if (operacao === 'delete') {
                nomeField.style.display = 'none';
                emailField.style.display = 'none';
                senhaField.style.display = 'none';
                idClienteField.style.display = 'block';
            } else if (operacao === 'update') {
                nomeField.style.display = 'block';
                emailField.style.display = 'block';
                senhaField.style.display = 'block';
                idClienteField.style.display = 'block';
            } else if (operacao === 'insert') {
                nomeField.style.display = 'block';
                emailField.style.display = 'block';
                senhaField.style.display = 'block';
                idClienteField.style.display = 'none';
            } else {
                nomeField.style.display = 'none';
                emailField.style.display = 'none';
                senhaField.style.display = 'none';
                idClienteField.style.display = 'none';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleFields();
            document.getElementById('operacao').addEventListener('change', toggleFields);
        });
    </script>
</head>
<body>
    <h1>Bem vindo ao Painel, <?php echo $_SESSION['nome']; ?></h1>

    <form action="" method="POST">

    <p>
        <label for="">Qual operação deseja Fazer</label>
        <select name="operacao" id="operacao">
            <option value=""></option>
            <option value="insert">Insert</option>
            <option value="update">Update</option>
            <option value="delete">Delete</option>
        </select>
    </p>

    <p id="idClienteField">
        <label for="">Se sua operação for de update</label>
        <br>
        <label for="">Id do cliente para o filtro: </label>
        <input type="text" name="id_cliente">
    </p>

    <p id="nomeField">
        <label for="">Nome do cliente:</label>
        <input type="text" name="nome">
    </p>
    <p id="emailField">
        <label for="">Email do Cliente:</label>
        <input type="email" name="email">
    </p>

    <p id="senhaField">
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