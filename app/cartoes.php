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

// Função para formatar o número do cartão
function formatarCartao($number) {
    return substr($number, 0, 4) . ' ' . substr($number, 4, 4) . ' ' . substr($number, 8, 4) . ' ' . substr($number, 12, 4);
}

// Consulta para obter os cartões de crédito do cliente logado
$id_cliente = $_SESSION['id_cliente'];
$sql_cartoes = "SELECT numero_cartao, limite_credito, 
                       DATE_FORMAT(data_vencimento, '%m/%Y') AS validade_formatada
                FROM cartoescredito
                WHERE id_cliente = $id_cliente
                ORDER BY data_vencimento ASC";

$result_cartoes = $mysqli->query($sql_cartoes);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Cartão de Crédito</title>
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

        .card {
            background: linear-gradient(135deg, #007bff 0%, #00c6ff 100%);
            color: white;
            padding: 40px; /* Aumenta o padding para estender o cartão para baixo */
            border-radius: 15px;
            margin-bottom: 30px; /* Aumenta a margem inferior para espaçamento */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            position: relative;
            max-width: 400px;
            margin: 20px auto;
            font-family: 'Courier New', Courier, monospace; /* Fonte monoespaçada para melhor visualização do número do cartão */
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card-content {
            position: relative;
            width: 100%;
            text-align: center;
            z-index: 1; /* Coloca o conteúdo do cartão à frente do chip */
        }

        .card-number {
            font-size: 1.4em;
            letter-spacing: 2px;
            margin-bottom: 10px;
            white-space: nowrap; /* Evitar quebra de linha no número do cartão */
        }

        .card-limit, .card-validity {
            font-size: 1em;
        }

        .card-logo {
            position: absolute;
            top: 65px;
            right: -20px;
            font-size: 1.2em;
            font-weight: bold;
        }

        .chip {
            width: 50px;
            height: 35px;
            background-color: #ffcc00;
            border-radius: 5px;
            position: absolute;
            top: -5px;
            left: -20px;
            z-index: 0; /* Coloca o chip atrás do conteúdo do cartão */
        }

        /* Estilo para o link de volta */
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
        <a href="painel_cliente.php" class="back-link">Voltar</a>
        <h1>Meu Cartão de Crédito</h1>

        <!-- Exibição dos cartões de crédito -->
        <?php
        if ($result_cartoes->num_rows > 0) {
            while ($row = $result_cartoes->fetch_assoc()) {
                $formatted_number = formatarCartao($row['numero_cartao']);
                echo "<div class='card'>
                        <div class='card-content'>
                            <div class='chip'></div>
                            <div class='card-logo'>VISA</div>
                            <div class='card-number'>{$formatted_number}</div>
                            <div class='card-limit'>Limite: R$ " .formatarDecimal($row['limite_credito']) . "</div>
                            <div class='card-validity'>Validade: {$row['validade_formatada']}</div>
                        </div>
                      </div>";
            }
        } else {
            echo "<p>Nenhum cartão de crédito encontrado.</p>";
        }
        ?>
    </div>
</body>
</html>
