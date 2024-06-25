<?php
// Inicia a sessão
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit;
}

// Inclui o arquivo de conexão com o banco de dados
include('conexao.php');

// Função para formatar valores decimais
function formatarDecimal($value) {
    return number_format($value, 2, ',', '.');
}

// Processamento do formulário para inserir nova transação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $numero_conta_destino = $mysqli->real_escape_string($_POST['numero_conta_destino']);
    $valor = $mysqli->real_escape_string($_POST['valor']);
    $id_cliente = $_SESSION['id_cliente'];

    // Recupera o número da conta de origem do cliente logado
    $sql_conta_origem = "SELECT numero_conta, saldo FROM contas WHERE id_cliente = $id_cliente";
    $result_conta_origem = $mysqli->query($sql_conta_origem);

    if ($result_conta_origem->num_rows > 0) {
        $row_conta_origem = $result_conta_origem->fetch_assoc();
        $numero_conta_origem = $row_conta_origem['numero_conta'];
        $saldo_origem = $row_conta_origem['saldo'];

        // Verifica se as contas são diferentes
        if ($numero_conta_origem === $numero_conta_destino) {
            echo '<div class="message error">As contas de origem e destino devem ser diferentes.</div>';
        } else {
            // Verifica se a conta de destino existe
            $sql_conta_destino = "SELECT id_cliente FROM contas WHERE numero_conta = '$numero_conta_destino'";
            $result_conta_destino = $mysqli->query($sql_conta_destino);

            if ($result_conta_destino->num_rows === 0) {
                echo '<div class="message error">Conta de destino não encontrada.</div>';
            } else {
                // Verifica se o saldo é suficiente
                if ($saldo_origem < $valor) {
                    echo '<div class="message error">Saldo insuficiente para realizar a transação.</div>';
                } else {
                    // Inicia uma transação SQL
                    $mysqli->begin_transaction();

                    // Insere a transação na tabela de transações
                    $sql_insert = "INSERT INTO transacoes (numero_conta_origem, numero_conta_destino, valor, data_transacao)
                                   VALUES (?, ?, ?, NOW())";
                    $stmt_insert = $mysqli->prepare($sql_insert);
                    $stmt_insert->bind_param("sss", $numero_conta_origem, $numero_conta_destino, $valor);

                    // Atualiza o saldo da conta de origem
                    $sql_update_origem = "UPDATE contas SET saldo = saldo - ? WHERE numero_conta = ?";
                    $stmt_update_origem = $mysqli->prepare($sql_update_origem);
                    $stmt_update_origem->bind_param("ss", $valor, $numero_conta_origem);

                    // Atualiza o saldo da conta de destino
                    $sql_update_destino = "UPDATE contas SET saldo = saldo + ? WHERE numero_conta = ?";
                    $stmt_update_destino = $mysqli->prepare($sql_update_destino);
                    $stmt_update_destino->bind_param("ss", $valor, $numero_conta_destino);

                    // Executa as operações dentro da transação
                    $transacao_ok = true;

                    $transacao_ok = $transacao_ok && $stmt_insert->execute();
                    $transacao_ok = $transacao_ok && $stmt_update_origem->execute();
                    $transacao_ok = $transacao_ok && $stmt_update_destino->execute();

                    if ($transacao_ok) {
                        // Finaliza a transação
                        $mysqli->commit();

                        // Redireciona para evitar o reenvio do formulário
                        header("Location: transacoes.php");
                        exit;
                    } else {
                        // Rollback em caso de erro na transação
                        $mysqli->rollback();

                        echo '<div class="message error">Falha ao realizar a transação.</div>';
                    }
                }
            }
        }
    } else {
        echo '<div class="message error">Conta de origem não encontrada.</div>';
    }
}

// Consulta para obter as transações do cliente logado
$id_cliente = $_SESSION['id_cliente'];
$sql_transacoes = "SELECT t.numero_conta_origem, t.numero_conta_destino, t.valor, t.data_transacao, 
                          CASE 
                              WHEN c1.id_cliente = $id_cliente THEN 'Saída'
                              WHEN c2.id_cliente = $id_cliente THEN 'Entrada'
                          END AS tipo_transacao,
                          c1.numero_conta AS conta_origem, c2.numero_conta AS conta_destino
                   FROM transacoes t
                   LEFT JOIN contas c1 ON t.numero_conta_origem = c1.numero_conta
                   LEFT JOIN contas c2 ON t.numero_conta_destino = c2.numero_conta
                   WHERE c1.id_cliente = $id_cliente OR c2.id_cliente = $id_cliente
                   ORDER BY t.data_transacao DESC";

$result_transacoes = $mysqli->query($sql_transacoes);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Transações</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative; /* Para posicionamento relativo */
        }

        h1 {
            text-align: center;
            color: #007bff;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .message {
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        form {
            margin-bottom: 20px;
        }

        form input, form select {
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            width: 100%;
        }

        form button {
            background-color: #007bff;
            color: #ffffff;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        form button:hover {
            background-color: #0056b3;
        }

        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
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
        <a href="painel_cliente.php" class="back-link">Voltar</a>
        <h1>Minhas Transações</h1>

        <!-- Formulário para inserir nova transação -->
        <form action="" method="POST">
            <!-- Campo oculto para número da conta de origem -->
            <input type="hidden" id="numero_conta_origem" name="numero_conta_origem" value="<?php echo $numero_conta_origem; ?>">

            <label for="numero_conta_destino">Número da Conta Destino:</label>
            <input type="text" id="numero_conta_destino" name="numero_conta_destino" required>

            <label for="valor">Valor:</label>
            <input type="text" id="valor" name="valor" required>

            <button type="submit">Realizar Transação</button>
        </form>

        <!-- Exibição das transações -->
        <?php
        // Consulta para obter as transações do cliente logado
        $id_cliente = $_SESSION['id_cliente'];
        $sql_transacoes = "SELECT t.numero_conta_origem, t.numero_conta_destino, t.valor, t.data_transacao, 
                                  CASE 
                                      WHEN c1.id_cliente = $id_cliente THEN 'Saída'
                                      WHEN c2.id_cliente = $id_cliente THEN 'Entrada'
                                  END AS tipo_transacao,
                                  c1.numero_conta AS conta_origem, c2.numero_conta AS conta_destino
                           FROM transacoes t
                           LEFT JOIN contas c1 ON t.numero_conta_origem = c1.numero_conta
                           LEFT JOIN contas c2 ON t.numero_conta_destino = c2.numero_conta
                           WHERE c1.id_cliente = $id_cliente OR c2.id_cliente = $id_cliente
                           ORDER BY t.data_transacao DESC
                           LIMIT 1";

        $result_transacoes = $mysqli->query($sql_transacoes);

        if ($result_transacoes->num_rows > 0) {
            echo '<table>
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Conta Origem</th>
                            <th>Conta Destino</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>';

            while ($row = $result_transacoes->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['tipo_transacao']}</td>
                        <td>{$row['conta_origem']}</td>
                        <td>{$row['conta_destino']}</td>
                        <td>R$ " . formatarDecimal($row['valor']) . "</td>
                        <td>{$row['data_transacao']}</td>
                      </tr>";
            }

            echo '</tbody>
                </table>';
        } else {
            echo '<p>Nenhuma transação encontrada.</p>';
        }
        ?>

    </div>
</body>
</html>
