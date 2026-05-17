<?php

include __DIR__ . "/cipherVegenere.php";

setlocale(LC_ALL, 'pt-BR');

$isAjax = $_SERVER["REQUEST_METHOD"] == "POST" &&
    isset($_SERVER['CONTENT_TYPE']) &&
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;

$response = ['success' => false, 'data' => null, 'error' => null];

if ($isAjax) {
    header('Content-Type: application/json');
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input || !isset($input['action'])) {
        $response['error'] = 'Ação não especificada';
        echo json_encode($response);
        exit;
    }

    try {
        if ($input['action'] === 'encrypt') {
            $message = $input['plainText'] ?? '';
            $key = $input['encryptKey'] ?? '';

            if (!$message || !$key) {
                $response['error'] = 'Mensagem e chave são obrigatórias';
            } else {
                $encrypted = encryptMessage($message, $key);
                if ($encrypted === null) {
                    $response['error'] = 'A chave deve conter apenas letras (sem acentos, pontuação, caracteres especiais ou números)';
                } else {
                    $response['data'] = $encrypted;
                    $response['success'] = true;
                }
            }
        } elseif ($input['action'] === 'decrypt') {
            $cipherText = $input['encrypted'] ?? '';
            $key = $input['keyword'] ?? '';

            if (!$cipherText || !$key) {
                $response['error'] = 'Mensagem criptografada e chave são obrigatórias';
            } else {
                $decrypted = decryptMessage($cipherText, $key);
                if ($decrypted === null) {
                    $response['error'] = 'A chave deve conter apenas letras (sem acentos, pontuação, caracteres especiais ou números)';
                } else {
                    $response['data'] = $decrypted;
                    $response['success'] = true;
                }
            }
        } elseif ($input['action'] === 'bruteForce') {
            $cipherText = $input['cipherText'] ?? '';

            if (!$cipherText) {
                $response['error'] = 'Texto cifrado é obrigatório';
            } else {
                $keyLength = findKeyLength($cipherText);

                if (!$keyLength) {
                    $response['error'] = 'Não foi possível determinar o comprimento da chave';
                } else {
                    $keyword = recoveryKeyword($keyLength, $cipherText);
                    $plainText = decryptMessage($cipherText, $keyword);

                    $response['data'] = [
                        'keyLength' => $keyLength,
                        'keyword' => $keyword,
                        'plainText' => $plainText
                    ];
                    $response['success'] = true;
                }
            }
        }
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

?>

<!DOCTYPE html>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criptografia de Vigenère</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #667eea;
        }

        .card h2 {
            font-size: 16px;
            color: #555;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input,
        textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 2px solid #e1e8ed;
            border-radius: 6px;
            font-family: inherit;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        textarea {
            resize: vertical;
            min-height: 120px;
        }

        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .result-box {
            background: #f0f7ff;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            word-break: break-all;
            color: #333;
            display: none;
        }

        .result-box.show {
            display: block;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .result-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .copy-btn {
            width: auto;
            padding: 8px 16px;
            margin-top: 10px;
            font-size: 13px;
            background: #667eea;
        }

        .separator {
            height: 1px;
            background: #e1e8ed;
            margin: 30px 0;
        }

        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            display: none;
        }

        .error.show {
            display: block;
        }

        .success {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            display: none;
        }

        .success.show {
            display: block;
        }

        .loading {
            display: none;
            text-align: center;
            color: #667eea;
            font-size: 14px;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #e1e8ed;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔐 Criptografia Vigenère</h1>

        <div class="card">
            <h2>📝 Criptografar Mensagem</h2>
            <div class="error" id="encryptError"></div>
            <form id="formEncrypt">
                <label for="plainText">Escreva a mensagem:</label>
                <textarea id="plainText" placeholder="Digite o texto a criptografar..." required></textarea>

                <label for="encryptKey">Chave de criptografia:</label>
                <input type="text" id="encryptKey" placeholder="Digite uma chave..." required>

                <div class="loading" id="encryptLoading">
                    <span class="spinner"></span> Criptografando...
                </div>
                <button type="submit" id="btnEncrypt">🔒 Criptografar</button>
            </form>

            <div class="result-box" id="encryptedResult">
                <div class="result-label">Mensagem Criptografada:</div>
                <div id="encryptedText"></div>
                <button type="button" class="copy-btn" onclick="copyToClipboard('encryptedText')">📋 Copiar</button>
            </div>
        </div>

        <div class="separator"></div>

        <div class="card">
            <h2>🔓 Descriptografar Mensagem</h2>
            <div class="error" id="decryptError"></div>
            <form id="formDecrypt">
                <label for="encrypted">Mensagem criptografada:</label>
                <input type="text" id="encrypted" placeholder="Cole a mensagem criptografada..." required>

                <label for="decryptKey">Chave de descriptografia:</label>
                <input type="text" id="decryptKey" placeholder="Digite a chave..." required>

                <div class="loading" id="decryptLoading">
                    <span class="spinner"></span> Descriptografando...
                </div>
                <button type="submit" id="btnDecrypt">🔑 Descriptografar</button>
            </form>

            <div class="result-box" id="decryptedResult">
                <div class="result-label">Mensagem Original:</div>
                <div id="decryptedText"></div>
                <button type="button" class="copy-btn" onclick="copyToClipboard('decryptedText')">📋 Copiar</button>
            </div>
        </div>

        <div class="separator"></div>

        <div class="card">
            <h2>⚔️ Quebrar Cifra (Força Bruta)</h2>
            <div class="error" id="bruteForceError"></div>
            <form id="formBruteForce">
                <label for="cipherTextBrute">Texto Cifrado:</label>
                <input type="text" id="cipherTextBrute" placeholder="Cole o texto cifrado..." required>

                <div class="loading" id="bruteForceLoading">
                    <span class="spinner"></span> Analisando cifra...
                </div>
                <button type="submit" id="btnBruteForce">⚔️ Quebrar Cifra</button>
            </form>

            <div class="result-box" id="bruteForceResult">
                <div class="result-label">Tamanho da Chave:</div>
                <div id="keyLengthResult" style="margin-bottom: 15px; font-weight: bold; color: #667eea;"></div>

                <div class="result-label">Chave Descoberta:</div>
                <div id="keywordResult" style="margin-bottom: 15px; font-weight: bold; color: #667eea;"></div>
                <button type="button" class="copy-btn" onclick="copyToClipboard('keywordResult')">📋 Copiar</button>

                <div style="margin-top: 20px; border-top: 1px solid #ccc; padding-top: 15px;">
                    <div class="result-label">Texto Descriptografado:</div>
                    <div id="bruteForceText"></div>
                    <button type="button" class="copy-btn" onclick="copyToClipboard('bruteForceText')">📋 Copiar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const formEncrypt = document.getElementById('formEncrypt');
        const formDecrypt = document.getElementById('formDecrypt');
        const formBruteForce = document.getElementById('formBruteForce');

        formEncrypt.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleEncrypt();
        });

        formDecrypt.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleDecrypt();
        });

        formBruteForce.addEventListener('submit', async (e) => {
            e.preventDefault();
            await handleBruteForce();
        });

        async function handleEncrypt() {
            const plainText = document.getElementById('plainText').value.trim();
            const encryptKey = document.getElementById('encryptKey').value.trim();
            const errorBox = document.getElementById('encryptError');
            const loadingBox = document.getElementById('encryptLoading');
            const resultBox = document.getElementById('encryptedResult');

            errorBox.classList.remove('show');
            errorBox.textContent = '';

            if (!plainText || !encryptKey) {
                errorBox.textContent = 'Preencha todos os campos';
                errorBox.classList.add('show');
                return;
            }

            try {
                loadingBox.classList.add('show');
                document.getElementById('btnEncrypt').disabled = true;

                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'encrypt',
                        plainText,
                        encryptKey
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('encryptedText').textContent = data.data;
                    resultBox.classList.add('show');
                    document.getElementById('encrypted').value = data.data;
                } else {
                    errorBox.textContent = data.error || 'Erro ao criptografar';
                    errorBox.classList.add('show');
                }
            } catch (error) {
                errorBox.textContent = 'Erro na requisição: ' + error.message;
                errorBox.classList.add('show');
            } finally {
                loadingBox.classList.remove('show');
                document.getElementById('btnEncrypt').disabled = false;
            }
        }

        async function handleDecrypt() {
            const encrypted = document.getElementById('encrypted').value.trim();
            const decryptKey = document.getElementById('decryptKey').value.trim();
            const errorBox = document.getElementById('decryptError');
            const loadingBox = document.getElementById('decryptLoading');
            const resultBox = document.getElementById('decryptedResult');

            errorBox.classList.remove('show');
            errorBox.textContent = '';

            if (!encrypted || !decryptKey) {
                errorBox.textContent = 'Preencha todos os campos';
                errorBox.classList.add('show');
                return;
            }

            try {
                loadingBox.classList.add('show');
                document.getElementById('btnDecrypt').disabled = true;

                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'decrypt',
                        encrypted,
                        keyword: decryptKey
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('decryptedText').textContent = data.data;
                    resultBox.classList.add('show');
                } else {
                    errorBox.textContent = data.error || 'Erro ao descriptografar';
                    errorBox.classList.add('show');
                }
            } catch (error) {
                errorBox.textContent = 'Erro na requisição: ' + error.message;
                errorBox.classList.add('show');
            } finally {
                loadingBox.classList.remove('show');
                document.getElementById('btnDecrypt').disabled = false;
            }
        }

        async function handleBruteForce() {
            const cipherText = document.getElementById('cipherTextBrute').value.trim();
            const errorBox = document.getElementById('bruteForceError');
            const loadingBox = document.getElementById('bruteForceLoading');
            const resultBox = document.getElementById('bruteForceResult');

            errorBox.classList.remove('show');
            errorBox.textContent = '';

            if (!cipherText) {
                errorBox.textContent = 'Digite o texto cifrado';
                errorBox.classList.add('show');
                return;
            }

            try {
                loadingBox.classList.add('show');
                document.getElementById('btnBruteForce').disabled = true;

                const response = await fetch(window.location.pathname, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'bruteForce',
                        cipherText
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('keyLengthResult').textContent = data.data.keyLength;
                    document.getElementById('keywordResult').textContent = data.data.keyword;
                    document.getElementById('bruteForceText').textContent = data.data.plainText;
                    resultBox.classList.add('show');
                } else {
                    errorBox.textContent = data.error || 'Erro ao quebrar cifra';
                    errorBox.classList.add('show');
                }
            } catch (error) {
                errorBox.textContent = 'Erro na requisição: ' + error.message;
                errorBox.classList.add('show');
            } finally {
                loadingBox.classList.remove('show');
                document.getElementById('btnBruteForce').disabled = false;
            }
        }

        function copyToClipboard(elementId) {
            const text = document.getElementById(elementId).textContent;
            navigator.clipboard.writeText(text).then(() => {
                alert('Copiado para a área de transferência!');
            });
        }
    </script>
</body>

</html>