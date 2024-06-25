<?php
session_start(); // Inicia a sessão, necessário se você estiver usando variáveis de sessão ($_SESSION)

include('protect.php'); // Arquivo de proteção (se necessário)
include('conexao.php'); // Arquivo de conexão com o banco de dados

$error_message = '';
$success_message = '';

// Processamento do formulário de inserção de pagamento agendado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $beneficiario_nome = $_POST['beneficiario_nome'];
    $numero_conta_beneficiario = $_POST['numero_conta_beneficiario'];
    $valor = $_POST['valor'];
    $data_agendamento = $_POST['data_agendamento'];

    // Validar os dados (exemplo simples)
    if (empty($beneficiario_nome) || empty($numero_conta_beneficiario) || empty($valor) || empty($data_agendamento)) {
        $error_message = "Preencha todos os campos.";
    } else {
        // Verificar se a conta do beneficiário existe na tabela de contas
        $sql_verifica_conta = "SELECT 1 FROM contas WHERE numero_conta = ?";
        $stmt_verifica_conta = $mysqli->prepare($sql_verifica_conta);
        $stmt_verifica_conta->bind_param("i", $numero_conta_beneficiario);
        $stmt_verifica_conta->execute();
        $stmt_verifica_conta->store_result();

        if ($stmt_verifica_conta->num_rows == 0) {
            // Conta do beneficiário não encontrada
            $error_message = '<div class="message">A conta do beneficiário não existe.</div>';
        } else {
            // Verificar se há saldo suficiente na conta do cliente
            $query_saldo = "SELECT saldo FROM contas WHERE id_cliente = ?";
            $stmt_saldo = $mysqli->prepare($query_saldo);
            $stmt_saldo->bind_param("i", $_SESSION['id_cliente']);
            $stmt_saldo->execute();
            $stmt_saldo->bind_result($saldo_atual);
            $stmt_saldo->fetch();
            $stmt_saldo->close();

            if ($saldo_atual < $valor) {
                // Saldo insuficiente
                $error_message = '<div class="message">Saldo insuficiente para realizar o pagamento.</div>';
            } else {
                // Iniciar transação para garantir integridade
                $mysqli->autocommit(false);

                // Inserir o pagamento agendado na tabela pagamentos_agendados
                $sql_inserir_pagamento = "INSERT INTO pagamentos_agendados (id_cliente, beneficiario_nome, numero_conta_beneficiario, valor, data_agendamento) 
                                         VALUES (?, ?, ?, ?, ?)";
                $stmt_inserir_pagamento = $mysqli->prepare($sql_inserir_pagamento);
                $stmt_inserir_pagamento->bind_param("issds", $_SESSION['id_cliente'], $beneficiario_nome, $numero_conta_beneficiario, $valor, $data_agendamento);

                // Atualizar o saldo na tabela contas
                $novo_saldo = $saldo_atual - $valor;
                $sql_atualizar_saldo = "UPDATE contas SET saldo = ? WHERE id_cliente = ?";
                $stmt_atualizar_saldo = $mysqli->prepare($sql_atualizar_saldo);
                $stmt_atualizar_saldo->bind_param("di", $novo_saldo, $_SESSION['id_cliente']);

                // Executar as queries dentro de uma transação
                $erro_transacao = false;
                $stmt_inserir_pagamento->execute();
                if ($stmt_inserir_pagamento->error) {
                    $erro_transacao = true;
                }
                $stmt_atualizar_saldo->execute();
                if ($stmt_atualizar_saldo->error) {
                    $erro_transacao = true;
                }

                // Commit ou rollback da transação
                if ($erro_transacao) {
                    $mysqli->rollback();
                    $error_message = '<div class="message">Erro ao agendar o pagamento.</div>';
                } else {
                    $mysqli->commit();
                    $success_message = '<div class="success-message">Pagamento agendado com sucesso!</div>';
                }

                // Fechar statements
                $stmt_inserir_pagamento->close();
                $stmt_atualizar_saldo->close();
            }
        }

        $stmt_verifica_conta->close();
    }
}

// Consulta para listar os pagamentos agendados do cliente atual
$sql_listagem = "SELECT id_pagamento, beneficiario_nome, numero_conta_beneficiario, valor, data_agendamento 
                 FROM pagamentos_agendados WHERE id_cliente = ?";
$stmt_listagem = $mysqli->prepare($sql_listagem);
$stmt_listagem->bind_param("i", $_SESSION['id_cliente']);
$stmt_listagem->execute();
$resultado = $stmt_listagem->get_result();

// Fechar statement após uso
$stmt_listagem->close();

// Consulta para obter o saldo da conta do cliente após a atualização
$query_saldo = "SELECT saldo FROM contas WHERE id_cliente = ?";
$stmt_saldo = $mysqli->prepare($query_saldo);
$stmt_saldo->bind_param("i", $_SESSION['id_cliente']);
$stmt_saldo->execute();
$stmt_saldo->bind_result($saldo_atual);
$stmt_saldo->fetch();
$stmt_saldo->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos Agendados</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 90%; /* Aumentar largura para 90% da largura da tela */
            max-width: 700px; /* Definir largura máxima para evitar expansão excessiva */
            position: relative; /* Para posicionamento absoluto do botão */
        }

        h1 {
            color: #007bff;
            margin-bottom: 20px;
            font-size: 24px;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        label {
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }

        input[type="text"], input[type="number"], input[type="date"], input[type="submit"] {
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus, input[type="number"]:focus, input[type="date"]:focus {
            border-color: #007bff;
            outline: none;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .message {
            color: red;
            text-align: center;
            margin-bottom: 10px;
        }

        .success-message {
            color: green;
            text-align: center;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        table th {
            background-color: #f2f2f2;
        }

        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        /* Estilos para o botão Voltar */
        .btn-voltar {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .back-link {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            position: absolute;
            top: 20px;
            left: 20px;
        }

        .back-link::before {
            content: "\00AB"; 
            margin-right: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Cabeçalho da página -->
         <a href="painel_cliente.php" class="back-link">Voltar</a>
        <h1>Pagamentos Agendados</h1>

        <!-- Exibição de mensagens de erro e sucesso -->
        <?php echo $error_message; ?>
        <?php echo $success_message; ?>

        <!-- Formulário para agendar um pagamento -->
        <form id="payment-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <label for="beneficiario_nome">Nome do Beneficiário:</label>
            <input type="text" id="beneficiario_nome" name="beneficiario_nome" required>

            <label for="numero_conta_beneficiario">Número da Conta do Beneficiário:</label>
            <input type="number" id="numero_conta_beneficiario" name="numero_conta_beneficiario" required>

            <label for="valor">Valor:</label>
            <input type="text" id="valor" name="valor" required>

            <label for="data_agendamento">Data de Agendamento:</label>
            <input type="date" id="data_agendamento" name="data_agendamento" required>

            <input type="submit" name="submit" value="Agendar Pagamento">
        </form>

        <!-- Tabela de pagamentos agendados -->
        <h2>Lista de Pagamentos Agendados</h2>
        <table>
            <thead>
                <tr>
                    <th>ID Pagamento</th>
                    <th>Beneficiário</th>
                    <th>Número da Conta</th>
                    <th>Valor</th>
                    <th>Data de Agendamento</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $resultado->fetch_assoc()) : ?>
                    <tr>
                        <td><?php echo $row['id_pagamento']; ?></td>
                        <td><?php echo $row['beneficiario_nome']; ?></td>
                        <td><?php echo $row['numero_conta_beneficiario']; ?></td>
                        <td><?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                        <td><?php echo $row['data_agendamento']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Script JavaScript -->
    <script>
        // Verifica se há mensagem de erro e a exibe por 1,5 segundos antes de permitir submeter o formulário novamente
        var errorMessage = document.querySelector('.message');
        if (errorMessage) {
            setTimeout(function() {
                errorMessage.style.display = 'none';
            }, 1500);
        }

       </script>
   </body>
   </html>
