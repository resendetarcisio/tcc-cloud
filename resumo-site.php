<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['url'])) {
    $url = filter_var($_POST['url'], FILTER_VALIDATE_URL);
    
    if ($url) {
        $htmlContent = @file_get_contents($url);
        
        if ($htmlContent !== false) {
            $apiKey = "{API key}";
            $summary = summarizeText($htmlContent, $apiKey);
        } else {
            $htmlContent = "Error: Unable to fetch the page content.";
            $summary = "N/A";
        }
    } else {
        $htmlContent = "Error: Invalid URL.";
        $summary = "N/A";
    }
}

function summarizeText($text, $apiKey) {
    $apiUrl = "https://api.openai.com/v1/chat/completions";
    
    $data = [
        "model" => "gpt-4o-mini",
        "messages" => [
            ["role" => "system", "content" => "Faça um resumo do conteúdo do site, tente identificar o conteúdo principal do site. Ignorando propagandas e outras parte irrelevantes:"],
            ["role" => "user", "content" => substr($text, 0, 128000)]
        ],
        "temperature" => 0.7,
    ];
    
    $options = [
        "http" => [
            "header" => "Content-Type: application/json\r\n" .
                        "Authorization: Bearer $apiKey\r\n",
            "method" => "POST",
            "content" => json_encode($data),
        ],
    ];
    
    $context = stream_context_create($options);
    $result = file_get_contents($apiUrl, false, $context);
    
    if ($result === false) {
        return "Error: Unable to get summary.";
    }
    
    $response = json_decode($result, true);
    return $response['choices'][0]['message']['content'] ?? "Error: No response from API.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumidor de Páginas</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        textarea { width: 100%; height: 200px; border: 1px solid #007BFF; padding: 10px; }
        .container { max-width: 800px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        .box { border: 2px solid #007BFF; padding: 10px; margin-top: 10px; border-radius: 5px; background: #eef6ff; }
        h2 { color: #007BFF; }
        button { background: #007BFF; color: white; border: none; padding: 10px 15px; cursor: pointer; border-radius: 5px; }
        button:hover { background: #0056b3; }
        input[type="url"] { width: calc(100% - 22px); padding: 8px; border: 1px solid #007BFF; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Resumidor de Páginas Web</h2>
        <form method="post">
            <label for="url">Entre com o link da página que deseja resumir:</label>
            <input type="url" name="url" required>
            <button type="submit">Buscar e resumir</button>
        </form>
        
        <?php if (!empty($htmlContent)) : ?>
            <div class="box">
                <h3>Conteúdo da página</h3>
                <textarea readonly><?php echo htmlspecialchars($htmlContent); ?></textarea>
            </div>
        <?php endif; ?>

        <?php if (!empty($summary)) : ?>
            <div class="box">
                <h3>Resumo</h3>
                <textarea readonly><?php echo htmlspecialchars($summary); ?></textarea>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>