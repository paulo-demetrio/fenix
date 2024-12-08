<?php
require 'vendor/autoload.php'; // Composer autoload, se usar bibliotecas externas

// Função para gerar o payload Pix
function gerarPayloadPix($chave, $valor, $descricao, $identificador) {
    return "00020101021226580014BR.GOV.BCB.PIX0136" . $chave .
        "520400005303986540" . str_pad(number_format($valor, 2, '', ''), 10, '0', STR_PAD_LEFT) .
        "5802BR5913Lojinha Fenix6009Sao Paulo62200525" . $identificador .
        "6304"; // O CRC será calculado depois
}

// Função para calcular o CRC16 do payload Pix
function calcularCRC16($payload) {
    $polinomio = 0x1021;
    $resultado = 0xFFFF;

    for ($offset = 0; $offset < strlen($payload); $offset++) {
        $resultado ^= (ord($payload[$offset]) << 8);
        for ($bitwise = 0; $bitwise < 8; $bitwise++) {
            if (($resultado & 0x8000) !== 0) {
                $resultado = (($resultado << 1) ^ $polinomio);
            } else {
                $resultado = ($resultado << 1);
            }
        }
    }
    return strtoupper(dechex($resultado & 0xFFFF));
}

// Dados do pagamento
$chavePix = "123e4567-e89b-12d3-a456-426614174000"; // Substitua pela chave Pix real
$valor = $_POST['valor'] ?? 0.00; // Valor do pagamento
$descricao = "Compra Fenix";
$identificador = uniqid();

// Gerar o payload
$payloadSemCRC = gerarPayloadPix($chavePix, $valor, $descricao, $identificador);
$crc = calcularCRC16($payloadSemCRC);
$payloadFinal = $payloadSemCRC . $crc;

// Gerar QR Code usando a biblioteca PHP QR Code
header('Content-Type: image/png');
QRcode::png($payloadFinal);



<?php
// Dados enviados pelo banco via POST (exemplo de webhook)
$dadosRecebidos = file_get_contents('php://input');
$dados = json_decode($dadosRecebidos, true);

// Valide a assinatura e processa o pagamento
if ($dados && $dados['status'] === 'CONCLUIDO') {
    // Atualizar o pedido no banco de dados
    $identificador = $dados['identificador']; // ID único enviado no Pix
    $conexao = new mysqli('localhost', 'usuario', 'senha', 'banco');
    $stmt = $conexao->prepare("UPDATE pedidos SET status = 'pago' WHERE identificador = ?");
    $stmt->bind_param('s', $identificador);
    $stmt->execute();
    $stmt->close();

    http_response_code(200); // Resposta OK
} else {
    http_response_code(400); // Dados inválidos
}

   CREATE TABLE pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identificador VARCHAR(50) NOT NULL UNIQUE,
    valor DECIMAL(10, 2) NOT NULL,
    status ENUM('pendente', 'pago') DEFAULT 'pendente'
);
