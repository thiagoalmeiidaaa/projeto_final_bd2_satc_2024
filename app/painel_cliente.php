<?php
// Inclui arquivos necessários
include('protect.php'); // Verifica se há uma sessão de cliente válida
include('conexao.php'); // Conexão com o banco de dados

// Verifica se o cliente está autenticado
if (!isset($_SESSION['id_cliente'])) {
    header("Location: login.php");
    exit;
}

// Consulta para obter o saldo da conta do cliente
$query = "SELECT saldo FROM contas WHERE id_cliente = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $_SESSION['id_cliente']);
$stmt->execute();
$stmt->bind_result($saldo);
$stmt->fetch();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel do Cliente</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #333;
            min-height: 100vh;
        }

        header {
            font-size: 28px;
            margin-bottom: 20px;
            color: #007bff;
            font-weight: bold;
        }

        .info {
            text-align: center;
            margin-bottom: 20px;
        }

        .info h1 {
            font-size: 22px;
            margin-bottom: 10px;
        }

        .saldo {
            font-size: 24px;
            color: #4CAF50;
            margin-top: 10px;
        }

        nav {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: 100%;
            max-width: 600px;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .menu {
            margin-top: 20px;
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .menu a {
            display: block;
            width: 100%;
            text-align: center;
            font-size: 18px;
            padding: 15px;
            margin: 5px 0;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;
        }

        .menu a:hover {
            background-color: #0056b3;
            transform: translateY(-2px);
        }

        .menu a:active {
            background-color: #003f7f;
            transform: translateY(0);
        }

        .logout {
            margin-top: 20px;
            font-size: 18px;
            color: #007bff;
        }

        .logout a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }

        .logout a:hover {
            color: #0056b3;
        }
    </style>
</head>
<body>
    <header>Painel do Cliente</header>
    <nav>
        <div class="info">
            <h1>Bem-vindo, <?php echo $_SESSION['nome']; ?>!</h1>
            <div class="saldo">
                Saldo atual: R$ <?php echo number_format($saldo, 2, ',', '.'); ?>
            </div>
        </div>
        <div class="menu">
            <a href="extrato.php">Extrato</a>
            <a href="cartoes.php">Cartões de Crédito</a>
            <a href="transacoes.php">Transações</a>
            <a href="emprestimos.php">Empréstimos</a>
            <a href="pagamentos.php">Pagamentos Agendados</a>
        </div>
        <div class="logout">
            <a href="logout.php">Sair</a>
        </div>
    </nav>
</body>
</html>
