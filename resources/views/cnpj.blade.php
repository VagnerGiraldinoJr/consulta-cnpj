<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado do CNPJ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>{{ $dados['nome'] }}</h1>
        <p><strong>Abertura:</strong> {{ $dados['abertura'] }}</p>
        <p><strong>Situação:</strong> {{ $dados['situacao'] }}</p>
        <p><strong>Tipo:</strong> {{ $dados['tipo'] }}</p>
        <p><strong>Fantasia:</strong> {{ $dados['fantasia'] }}</p>
        <p><strong>Porte:</strong> {{ $dados['porte'] }}</p>
        <p><strong>Atividade Principal:</strong> {{ $dados['atividade_principal'][0]['text'] }}</p>
        <p><strong>Endereço:</strong> {{ $dados['logradouro'] }}, {{ $dados['numero'] }} - {{ $dados['bairro'] }} - {{ $dados['municipio'] }}/{{ $dados['uf'] }}</p>
        <p><strong>Telefone:</strong> {{ $dados['telefone'] }}</p>
    </div>
</body>
</html>
