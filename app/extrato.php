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

// Consulta para obter as transações do cliente logado
$id_cliente = $_SESSION['id_cliente'];
$sql_transacoes = "SELECT t.numero_conta_origem, t.numero_conta_destino, t.valor, 
                          DATE_FORMAT(t.data_transacao, '%d/%m/%Y %H:%i:%s') AS data_transacao_formatada,
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
    <title>Extrato de Transações</title>
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

        /* Estilo para o link de volta */
        .back-link {
            position: absolute;
            top: 20px;
            left: 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        .back-link::before {
            content: "\00AB"; /* Código unicode para seta dupla << */
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="painel_cliente.php" class="back-link">Voltar</a>
        <h1>Extrato de Transações</h1>

        <!-- Tabela de transações -->
        <table>
            <thead>
                <tr>
                    <th>Tipo da Transação</th>
                    <th>Conta Origem</th>
                    <th>Conta Destino</th>
                    <th>Valor</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result_transacoes->num_rows > 0) {
                    while ($row = $result_transacoes->fetch_assoc()) {
                        echo "<tr>
                                <td>{$row['tipo_transacao']}</td>
                                <td>{$row['conta_origem']}</td>
                                <td>{$row['conta_destino']}</td>
                                <td>R$ " . formatarDecimal($row['valor']) . "</td>
                                <td>{$row['data_transacao_formatada']}</td>
                              </tr>";
                    }
                } else {
                    echo "<tr><td colspan='5'>Nenhuma transação encontrada.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>
