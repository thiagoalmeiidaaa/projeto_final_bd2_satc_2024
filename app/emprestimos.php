<?php
// Inicia a sessão
session_start();

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

// Processamento do formulário para adicionar um novo empréstimo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $montante = $mysqli->real_escape_string($_POST['montante']);
    $taxa_juros = $mysqli->real_escape_string($_POST['taxa_juros']);
    $data_inicio = $mysqli->real_escape_string($_POST['data_inicio']);
    $data_fim = $mysqli->real_escape_string($_POST['data_fim']);
    $status = 'Ativo';  // Novo empréstimo começa com status 'Ativo'
    $id_cliente = $_SESSION['id_cliente'];

    // Inicia uma transação SQL
    $mysqli->begin_transaction();

    // Insere o empréstimo na tabela de empréstimos
    $sql_insert = "INSERT INTO emprestimos (id_cliente, montante, taxa_juros, data_inicio, data_fim, status)
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_insert = $mysqli->prepare($sql_insert);
    $stmt_insert->bind_param("iddsss", $id_cliente, $montante, $taxa_juros, $data_inicio, $data_fim, $status);

    // Atualiza o saldo da conta do cliente
    $sql_update_conta = "UPDATE contas SET saldo = saldo + ? WHERE id_cliente = ?";
    $stmt_update_conta = $mysqli->prepare($sql_update_conta);
    $stmt_update_conta->bind_param("di", $montante, $id_cliente);

    // Executa as operações dentro da transação
    $emprestimo_ok = true;

    $emprestimo_ok = $emprestimo_ok && $stmt_insert->execute();
    $emprestimo_ok = $emprestimo_ok && $stmt_update_conta->execute();

    if ($emprestimo_ok) {
        // Finaliza a transação
        $mysqli->commit();

        // Redireciona para evitar o reenvio do formulário
        header("Location: emprestimos.php");
        exit;
    } else {
        // Rollback em caso de erro na transação
        $mysqli->rollback();

        echo '<div class="message error">Falha ao realizar o empréstimo.</div>';
    }
}

// Consulta para obter os empréstimos do cliente logado
$id_cliente = $_SESSION['id_cliente'];
$sql_emprestimos = "SELECT montante, taxa_juros, 
                           DATE_FORMAT(data_inicio, '%d/%m/%Y') AS data_inicio_formatada,
                           DATE_FORMAT(data_fim, '%d/%m/%Y') AS data_fim_formatada,
                           status
                    FROM emprestimos
                    WHERE id_cliente = $id_cliente
                    ORDER BY data_inicio DESC";

$result_emprestimos = $mysqli->query($sql_emprestimos);
?>


<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Empréstimos</title>
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
        <h1>Meus Empréstimos</h1>

        <!-- Formulário para adicionar novo empréstimo -->
        <form action="" method="POST">
            <label for="montante">Montante:</label>
            <input type="text" id="montante" name="montante" required>

            <label for="taxa_juros">Taxa de Juros (%):</label>
            <input type="text" id="taxa_juros" name="taxa_juros" required>

            <label for="data_inicio">Data de Início:</label>
            <input type="date" id="data_inicio" name="data_inicio" required>

            <label for="data_fim">Data de Fim:</label>
            <input type="date" id="data_fim" name="data_fim" required>

            <button type="submit">Realizar Empréstimo</button>
        </form>

        <!-- Exibição dos empréstimos -->
        <?php
        if ($result_emprestimos->num_rows > 0) {
            echo '<table>
                    <thead>
                        <tr>
                            <th>Montante</th>
                            <th>Taxa de Juros (%)</th>
                            <th>Data de Início</th>
                            <th>Data de Fim</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>';

            while ($row = $result_emprestimos->fetch_assoc()) {
                echo "<tr>
                        <td>R$ " . formatarDecimal($row['montante']) . "</td>
                        <td>" . formatarDecimal($row['taxa_juros']) . "%</td>
                        <td>{$row['data_inicio_formatada']}</td>
                        <td>{$row['data_fim_formatada']}</td>
                        <td>{$row['status']}</td>
                      </tr>";
            }

            echo '</tbody>
                </table>';
        } else {
            echo '<p>Nenhum empréstimo encontrado.</p>';
        }
        ?>

    </div>
</body>
</html>