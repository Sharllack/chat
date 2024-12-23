<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\Server\IoServer;

class ChatServer implements MessageComponentInterface
{
    private $clients;
    private $db;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;

        $dsn = 'mysql:host=localhost;dbname=chat;charset=utf8';
        $username = 'root';
        $password = 'LoideMartha12*';

        try {
            $this->db = new PDO($dsn, $username, $password);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "Nova conexão aberta ({$conn->resourceId})\n";

        // Log de cabeçalhos HTTP recebidos
        $headers = $conn->httpRequest->getHeaders();
        echo "Cabeçalhos recebidos:\n";
        foreach ($headers as $key => $values) {
            echo "$key: " . implode(', ', $values) . "\n";
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $from->send(json_encode(['error' => 'Formato de mensagem inválido.']));
            return;
        }

        if (isset($data['id_usuario'])) {
            $from->user_id = $data['id_usuario'];
            echo "Usuário conectado: {$from->user_id}\n";
        }

        if ($data['action'] === 'connect') {
            $idUsuario = $from->user_id;

            $stmt = $this->db->prepare("SELECT mensagens.*, usuario.*
                FROM mensagens
                JOIN usuario ON mensagens.id_usuario = usuario.id
                WHERE mensagens.id_usuario = :id_usuario
                   OR mensagens.id_usuario != :id_usuario
                ORDER BY mensagens.data ASC");
            $stmt->execute([':id_usuario' => $idUsuario]);
            $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($mensagens)) {
                $from->send(json_encode($mensagens));
            } else {
                $from->send(json_encode(['error' => 'Não foi possível recuperar as mensagens']));
            }
        }

        if ($data['action'] === 'send_message') {
            $idUsuario = $from->user_id;
            $mensagem = htmlspecialchars($data['mensagem'], ENT_QUOTES, 'UTF-8');

            $stmt = $this->db->prepare("INSERT INTO mensagens (id_usuario, mensagem, data) VALUES (:id_usuario, :mensagem, NOW())");
            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':mensagem' => $mensagem,
            ]);

            $stmt = $this->db->prepare("SELECT mensagens.*, usuario.*
                FROM mensagens
                JOIN usuario ON mensagens.id_usuario = usuario.id
                WHERE mensagens.id = :id_mensagem");
            $stmt->execute([':id_mensagem' => $this->db->lastInsertId()]);
            $messageData = $stmt->fetch(PDO::FETCH_ASSOC);

            foreach ($this->clients as $client) {
                $client->send(json_encode($messageData));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        echo "Conexão encerrada ({$conn->resourceId})\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Inicializa o servidor WebSocket
$port = 8080;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    $port,
    '0.0.0.0'
);

echo "Servidor WebSocket rodando na porta {$port}\n";
$server->run();
