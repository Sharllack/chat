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

        // Suponha que o user_id seja enviado no cabeçalho
        $headers = $conn->httpRequest->getHeaders();
        $userId = $headers['User-ID'][0] ?? null; // Substitua por como você obtém o user_id
        $conn->user_id = $userId;

        echo "Usuário conectado com ID: {$userId}\n";

        // Enviar lista de usuários online para o novo cliente
        $onlineUsers = [];
        foreach ($this->clients as $client) {
            if ($client !== $conn && isset($client->user_id)) {
                $onlineUsers[] = $client->user_id;
            }
        }

        $conn->send(json_encode([
            'action' => 'online_users',
            'users' => $onlineUsers,
        ]));

        // Notificar outros clientes que um novo usuário entrou
        foreach ($this->clients as $client) {
            if ($client !== $conn) {
                $client->send(json_encode([
                    'action' => 'user_joined',
                    'user_id' => $userId,
                ]));
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $from->send(json_encode(['error' => 'Formato de mensagem inválido.']));
            return;
        }

        // Verifica se o ID do usuário já foi associado para evitar repetição
        if (!isset($from->user_id) && isset($data['id_usuario'])) {
            $from->user_id = $data['id_usuario'];
            echo "Usuário conectado: {$from->user_id}\n";
        }

        // Gerenciamento de status "digitando"
        if ($data['action'] === 'typing' || $data['action'] === 'stop_typing') {

            foreach ($this->clients as $client) {
                if ($client !== $from) {
                    $client->send(json_encode([
                        'action' => $data['action'],
                        'user_id' => $from->user_id,
                    ]));
                }
            }
            return; // Não continua processando como mensagem normal
        }

        // Recuperação de mensagens ao conectar
        // Recuperação de mensagens ao conectar
        if ($data['action'] === 'connect') {
            $idUsuario = $from->user_id;

            $stmt = $this->db->prepare("SELECT mensagens.*, usuario.* 
                FROM mensagens
                JOIN usuario ON mensagens.id_usuario = usuario.id
                WHERE (mensagens.id_usuario = :id_usuario OR mensagens.id_usuario != :id_usuario)
                AND mensagens.status = 'ativo'
                ORDER BY mensagens.data ASC");
            $stmt->execute([':id_usuario' => $idUsuario]);
            $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (is_array($mensagens)) {
                $from->send(json_encode($mensagens));
            } else {
                $from->send(json_encode(['error' => 'Não foi possível recuperar as mensagens']));
            }
        }

        // Envio de mensagens
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
            WHERE mensagens.id_mensagem = :id_mensagem
            AND mensagens.status = 'ativo'");
            $stmt->execute([':id_mensagem' => $this->db->lastInsertId()]);
            $messageData = $stmt->fetch(PDO::FETCH_ASSOC);

            foreach ($this->clients as $client) {
                $client->send(json_encode($messageData));
            }
        }

        //excluir mensagens
        if ($data['action'] === 'exclude_message') {
            $id_mensagem = $data['id_mensagem'];

            // Atualize o status da mensagem corretamente
            $stmt = $this->db->prepare("UPDATE mensagens SET status = 'inativo' WHERE id_mensagem = :id_mensagem");
            $stmt->execute([':id_mensagem' => $id_mensagem]);

            // Recupere as mensagens após a exclusão
            $stmt = $this->db->prepare("SELECT mensagens.*, usuario.* 
                                        FROM mensagens 
                                        JOIN usuario ON mensagens.id_usuario = usuario.id
                                        AND mensagens.status = 'ativo'
                                        ORDER BY mensagens.data ASC");
            $stmt->execute();
            $message = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Envie a mensagem atualizada para todos os clientes conectados
            foreach ($this->clients as $client) {
                $client->send(json_encode($message));
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $userId = $conn->user_id ?? null; // Obtenha o ID do usuário, se estiver definido
        $this->clients->detach($conn);
        echo "Conexão encerrada ({$conn->resourceId})\n";

        if ($userId) {
            // Notifica todos os clientes que o usuário desconectou
            foreach ($this->clients as $client) {
                $client->send(json_encode([
                    'action' => 'user_left',
                    'user_id' => $userId, // ID do usuário que desconectou
                ]));
            }
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Erro: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Inicializa o servidor WebSocket
$context = [
    'ssl' => [
        'local_cert' => 'C:/Users/judoc/server_cert.pem',
        'local_pk' => 'C:/Users/judoc/private_key.pem',
        'allow_self_signed' => true,
        'verify_peer' => false,
    ],
];

$port = 8080;
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    $port,
    '0.0.0.0',
    $context // Aqui você aplica o SSL
);

echo "Servidor WebSocket rodando na porta {$port}\n";
$server->run();
