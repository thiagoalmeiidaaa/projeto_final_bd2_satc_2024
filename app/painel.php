<?php
session_start(); // Inicia a sessão, necessário se você estiver usando variáveis de sessão ($_SESSION)

include('protect.php'); // Arquivo de proteção (se necessário)
include('conexao.php'); // Arquivo de conexão com o banco de dados

$error_message = '';
$nome = '';
$email = '';
$senha = '';
$id_cliente = '';

if(isset($_POST['operacao'])) {
    $operacao = $mysqli->real_escape_string($_POST['operacao']);
    
    if ($operacao === 'delete') {
        $id_cliente = isset($_POST['id_cliente']) ? intval($_POST['id_cliente']) : 0;

        // Verificar se o cliente com o ID especificado existe na tabela clientes
        $sql_verifica = "SELECT 1 FROM clientes WHERE id_cliente = ?";
        $stmt_verifica = $mysqli->prepare($sql_verifica);
        $stmt_verifica->bind_param("i", $id_cliente);
        $stmt_verifica->execute();
        $stmt_verifica->store_result();

        if ($stmt_verifica->num_rows === 0) {
            $error_message = "Cliente não encontrado.";
        } else {
            // Cliente encontrado, proceder com a exclusão

            // Iniciar uma transação
            $mysqli->begin_transaction();

            try {
                // Deletar os registros de histórico de login relacionados ao cliente
                $sql_delete_historico_login = "DELETE FROM historico_login WHERE id_cliente = ?";
                $stmt_delete_historico_login = $mysqli->prepare($sql_delete_historico_login);
                $stmt_delete_historico_login->bind_param("i", $id_cliente);
                if (!$stmt_delete_historico_login->execute()) {
                    throw new Exception("Falha ao executar a exclusão do histórico de login: " . $stmt_delete_historico_login->error);
                }
            
                // Deletar os registros de pagamentos agendados relacionados ao cliente
                $sql_delete_pagamentos_agendados = "DELETE FROM pagamentos_agendados WHERE id_cliente = ?";
                $stmt_delete_pagamentos_agendados = $mysqli->prepare($sql_delete_pagamentos_agendados);
                $stmt_delete_pagamentos_agendados->bind_param("i", $id_cliente);
                if (!$stmt_delete_pagamentos_agendados->execute()) {
                    throw new Exception("Falha ao executar a exclusão dos pagamentos agendados: " . $stmt_delete_pagamentos_agendados->error);
                }
            
                // Deletar as transações onde o cliente é o titular da conta origem ou destino
                $sql_delete_transacoes = "DELETE FROM transacoes 
                                          WHERE numero_conta_origem IN (SELECT numero_conta FROM contas WHERE id_cliente = ?) 
                                          OR numero_conta_destino IN (SELECT numero_conta FROM contas WHERE id_cliente = ?)";
                $stmt_delete_transacoes = $mysqli->prepare($sql_delete_transacoes);
                $stmt_delete_transacoes->bind_param("ii", $id_cliente, $id_cliente);
                if (!$stmt_delete_transacoes->execute()) {
                    throw new Exception("Falha ao executar a exclusão das transações: " . $stmt_delete_transacoes->error);
                }
            
                // Deletar os registros de extrato relacionados às contas do cliente
                $sql_delete_extrato = "DELETE FROM extrato 
                                       WHERE numero_conta IN (SELECT numero_conta FROM contas WHERE id_cliente = ?)";
                $stmt_delete_extrato = $mysqli->prepare($sql_delete_extrato);
                $stmt_delete_extrato->bind_param("i", $id_cliente);
                if (!$stmt_delete_extrato->execute()) {
                    throw new Exception("Falha ao executar a exclusão do extrato: " . $stmt_delete_extrato->error);
                }
            
                // Deletar os empréstimos relacionados ao cliente
                $sql_delete_emprestimos = "DELETE FROM emprestimos WHERE id_cliente = ?";
                $stmt_delete_emprestimos = $mysqli->prepare($sql_delete_emprestimos);
                $stmt_delete_emprestimos->bind_param("i", $id_cliente);
                if (!$stmt_delete_emprestimos->execute()) {
                    throw new Exception("Falha ao executar a exclusão dos empréstimos: " . $stmt_delete_emprestimos->error);
                }
            
                // Deletar as contas relacionadas ao cliente
                $sql_delete_contas = "DELETE FROM contas WHERE id_cliente = ?";
                $stmt_delete_contas = $mysqli->prepare($sql_delete_contas);
                $stmt_delete_contas->bind_param("i", $id_cliente);
                if (!$stmt_delete_contas->execute()) {
                    throw new Exception("Falha ao executar a exclusão das contas: " . $stmt_delete_contas->error);
                }
            
                // Deletar os cartões de crédito relacionados ao cliente
                $sql_delete_cartoes = "DELETE FROM cartoescredito WHERE id_cliente = ?";
                $stmt_delete_cartoes = $mysqli->prepare($sql_delete_cartoes);
                $stmt_delete_cartoes->bind_param("i", $id_cliente);
                if (!$stmt_delete_cartoes->execute()) {
                    throw new Exception("Falha ao executar a exclusão dos cartões de crédito: " . $stmt_delete_cartoes->error);
                }
            
                // Deletar o cliente
                $sql_delete_cliente = "DELETE FROM clientes WHERE id_cliente = ?";
                $stmt_delete_cliente = $mysqli->prepare($sql_delete_cliente);
                $stmt_delete_cliente->bind_param("i", $id_cliente);
                if (!$stmt_delete_cliente->execute()) {
                    throw new Exception("Falha ao executar a exclusão do cliente: " . $stmt_delete_cliente->error);
                }
            
                // Se tudo estiver ok, faz o commit
                $mysqli->commit();
            
                echo "Operação '$operacao' executada com sucesso!";
                header("Location: ".$_SERVER['PHP_SELF']);
                exit;
            } catch (Exception $e) {
                // Se houve erro, faz o rollback
                $mysqli->rollback();
                $error_message = "Erro: " . $e->getMessage();
            } finally {
                if (isset($stmt_delete_historico_login)) $stmt_delete_historico_login->close();
                if (isset($stmt_delete_pagamentos_agendados)) $stmt_delete_pagamentos_agendados->close();
                if (isset($stmt_delete_transacoes)) $stmt_delete_transacoes->close();
                if (isset($stmt_delete_extrato)) $stmt_delete_extrato->close();
                if (isset($stmt_delete_emprestimos)) $stmt_delete_emprestimos->close();
                if (isset($stmt_delete_contas)) $stmt_delete_contas->close();
                if (isset($stmt_delete_cartoes)) $stmt_delete_cartoes->close();
                if (isset($stmt_delete_cliente)) $stmt_delete_cliente->close();
            }
        }
        $stmt_verifica->close();
    } elseif ($operacao === 'insert' || $operacao === 'update') {
        if ((strlen($_POST['nome']) == 0) && ($operacao != 'delete')) {
            $error_message = "Preencha o nome";
        } else if ((strlen($_POST['email']) == 0) && ($operacao != 'delete')) {
            $error_message = "Preencha o email";
        } else if ((strlen($_POST['senha']) == 0) && ($operacao != 'delete')) {
            $error_message = "Preencha a senha";
        } else {
            $id_cliente = isset($_POST['id_cliente']) ? $mysqli->real_escape_string($_POST['id_cliente']) : null;
            $nome = $mysqli->real_escape_string($_POST['nome']);
            $email = $mysqli->real_escape_string($_POST['email']);
            $senha = $mysqli->real_escape_string($_POST['senha']);

            if ($operacao == 'insert') {
                // Iniciar uma transação
                $mysqli->begin_transaction();

                try {
                    // Insere o cliente na tabela clientes
                    $sql_cliente = "INSERT INTO clientes(nome, email, senha, data_cadastro) VALUES (?, ?, ?, NOW())";
                    $stmt_cliente = $mysqli->prepare($sql_cliente);
                    if (!$stmt_cliente) {
                        throw new Exception("Falha ao preparar a inserção do cliente: " . $mysqli->error);
                    }
                    $stmt_cliente->bind_param("sss", $nome, $email, $senha);
                    if (!$stmt_cliente->execute()) {
                        throw new Exception("Falha ao executar a inserção do cliente: " . $stmt_cliente->error);
                    }
                    $id_cliente = $stmt_cliente->insert_id; // Obtém o ID do cliente inserido

                    // Insere um cartão de crédito na tabela cartoescredito
                    $numero_cartao = str_pad(mt_rand(0, 9999999999999999), 16, '0', STR_PAD_LEFT);
                    $limite_credito = 2500.00;
                    $data_vencimento = date('Y-m-d', strtotime('+4 years')); // Vencimento daqui a 4 anos

                    $sql_cartao = "INSERT INTO cartoescredito (id_cliente, numero_cartao, limite_credito, data_vencimento) VALUES (?, ?, ?, ?)";
                    $stmt_cartao = $mysqli->prepare($sql_cartao);
                    if (!$stmt_cartao) {
                        throw new Exception("Falha ao preparar a inserção do cartão de crédito: " . $mysqli->error);
                    }
                    $stmt_cartao->bind_param("isds", $id_cliente, $numero_cartao, $limite_credito, $data_vencimento);
                    if (!$stmt_cartao->execute()) {
                        throw new Exception("Falha ao executar a inserção do cartão de crédito: " . $stmt_cartao->error);
                    }

                    // Insere uma conta na tabela contas
                    $saldo_inicial = 0.00;
                    $data_abertura = date('Y-m-d');

                    $sql_conta = "INSERT INTO contas (id_cliente, saldo, data_abertura) VALUES (?, ?, ?)";
                    $stmt_conta = $mysqli->prepare($sql_conta);
                    if (!$stmt_conta) {
                        throw new Exception("Falha ao preparar a inserção da conta: " . $mysqli->error);
                    }
                    $stmt_conta->bind_param("ids", $id_cliente, $saldo_inicial, $data_abertura);
                    if (!$stmt_conta->execute()) {
                        throw new Exception("Falha ao executar a inserção da conta: " . $stmt_conta->error);
                    }

                    // Se tudo estiver ok, faz o commit
                    $mysqli->commit();

                    echo "Operação '$operacao' executada com sucesso!";
                    header("Location: ".$_SERVER['PHP_SELF']);
                    exit;
                } catch (Exception $e) {
                    // Se houve erro, faz o rollback
                    $mysqli->rollback();
                    $error_message = "Erro: " . $e->getMessage();
                } finally {
                    $stmt_cliente->close();
                    if (isset($stmt_cartao)) $stmt_cartao->close();
                    if (isset($stmt_conta)) $stmt_conta->close();
                    $mysqli->close();
                }
            } else if ($operacao == 'update') {
                $sql_verifica = "SELECT 1 FROM clientes WHERE id_cliente = ?";
                $stmt_verifica = $mysqli->prepare($sql_verifica);
                $stmt_verifica->bind_param("i", $id_cliente);
                $stmt_verifica->execute();
                $stmt_verifica->store_result();

                if ($stmt_verifica->num_rows > 0) {
                    $stmt_verifica->close();
                    $sql_code = "UPDATE clientes SET nome=?, email=?, senha=? WHERE id_cliente=?";
                    $stmt_update = $mysqli->prepare($sql_code);
                    $stmt_update->bind_param("sssi", $nome, $email, $senha, $id_cliente);
                    $stmt_update->execute();

                    if ($stmt_update) {
                        echo "Operação '$operacao' executada com sucesso!";
                        header("Location: ".$_SERVER['PHP_SELF']);
                        exit;
                    }
                    $stmt_update->close();
                } else {
                    $error_message = "Cliente não encontrado.";
                    // Preserva os valores no formulário em caso de erro
                    $_SESSION['form_data'] = $_POST;
                }
            }
        }
    }
} elseif (isset($_SESSION['form_data'])) {
    // Preenche os valores do formulário com os dados da sessão em caso de erro
    $nome = $_SESSION['form_data']['nome'];
    $email = $_SESSION['form_data']['email'];
    $senha = $_SESSION['form_data']['senha'];
    $id_cliente = $_SESSION[    'form_data']['id_cliente'];
    unset($_SESSION['form_data']); // Limpa os dados da sessão após usá-los
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        h1 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 24px;
        }

        form {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            max-width: 90%;
        }

        label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
        }

        select,
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        select:focus,
        input[type="text"]:focus,
        input[type="email"]:focus,
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

        .message {
            color: red;
            text-align: center;
            margin-bottom: 20px;
            display: none; /* Inicialmente oculto */
        }

        a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }

        a:hover {
            color: #0056b3;
        }

        p {
            margin: 0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Bem vindo ao Painel, <?php echo $_SESSION['nome']; ?>!</h1>

    <form action="" method="POST">
        <?php if ($error_message && in_array($operacao, ['update', 'delete'])): ?>
            <div class="message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <p>
            <label for="operacao">Qual operação deseja Fazer</label>
            <select name="operacao" id="operacao">
                <option value=""></option>
                <option value="insert" <?php if(isset($_POST['operacao']) && $_POST['operacao'] == 'insert') echo 'selected'; ?>>Insert</option>
                <option value="update" <?php if(isset($_POST['operacao']) && $_POST['operacao'] == 'update') echo 'selected'; ?>>Update</option>
                <option value="delete" <?php if(isset($_POST['operacao']) && $_POST['operacao'] == 'delete') echo 'selected'; ?>>Delete</option>
            </select>
        </p>

        <p id="idClienteField" style="display: <?php echo ($operacao == 'delete' || $operacao == 'update') ? 'block' : 'none'; ?>">
            <label for="id_cliente">ID do cliente para a operação:</label>
            <input type="text" id="id_cliente" name="id_cliente" value="<?php echo htmlspecialchars($id_cliente); ?>">
        </p>

        <p id="nomeField" style="display: <?php echo ($operacao == 'insert' || $operacao == 'update') ? 'block' : 'none'; ?>">
            <label for="nome">Nome do cliente:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($nome); ?>">
        </p>
        
        <p id="emailField" style="display: <?php echo ($operacao == 'insert' || $operacao == 'update') ? 'block' : 'none'; ?>">
            <label for="email">Email do Cliente:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
        </p>

        <p id="senhaField" style="display: <?php echo ($operacao == 'insert' || $operacao == 'update') ? 'block' : 'none'; ?>">
            <label for="senha">Senha:</label>
            <input type="password" id="senha" name="senha" value="<?php echo htmlspecialchars($senha); ?>">
        </p>

        <p>
            <button type="submit">Enviar</button>
        </p>

        <!-- Mensagem de erro -->
        <div id="errorMessage" class="message"></div>
    </form>

    <p>
        <a href="logout.php">Sair</a>
    </p>

    <!-- Script JavaScript adicionado -->
    <script>
        function mostrarCampos() {
            const operacao = document.getElementById('operacao').value;
            const nomeField = document.getElementById('nomeField');
            const emailField = document.getElementById('emailField');
            const senhaField = document.getElementById('senhaField');
            const idClienteField = document.getElementById('idClienteField');

            // Limpar valores dos campos ao mudar a operação
            document.getElementById('id_cliente').value = '';
            document.getElementById('nome').value = '';
            document.getElementById('email').value = '';
            document.getElementById('senha').value = '';

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

            // Ocultar mensagem de erro se a operação não for update ou delete
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.style.display = (operacao === 'update' || operacao === 'delete') ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', () => {
            mostrarCampos();
            document.getElementById('operacao').addEventListener('change', mostrarCampos);
        });

        function mostrarErro(message) {
            const errorMessage = document.getElementById('errorMessage');
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';

            setTimeout(() => {
                errorMessage.style.display = 'none';
            }, 1500); 
        }


        <?php if ($error_message && in_array($operacao, ['update', 'delete'])): ?>
            mostrarErro('<?php echo $error_message; ?>');
        <?php endif; ?>
    </script>
</body>
</html>