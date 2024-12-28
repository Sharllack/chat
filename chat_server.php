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
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $queryParams);

        if (isset($queryParams['id_usuario2'])) {
            $conn->id_usuario2 = $queryParams['id_usuario2'];
            echo "Conexão iniciada com Id_usuario2: {$conn->id_usuario2}\n";
        }

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
        if ($data['action'] === 'connect') {
            $idUsuario = $from->user_id;
            $id_usuario2 = $from->id_usuario2;

            // Consulta para obter os dados do usuário 2
            $stmtUsuarios = $this->db->prepare("SELECT id, usuario, imagem 
                                                FROM usuario 
                                                WHERE id = :id_usuario2");
            $stmtUsuarios->execute([':id_usuario2' => $id_usuario2]);
            $usuario2 = $stmtUsuarios->fetch(PDO::FETCH_ASSOC);

            // Consulta para obter as mensagens
            $stmtMensagens = $this->db->prepare("SELECT mensagens.*, usuario.usuario, usuario.imagem 
                                                 FROM mensagens
                                                 JOIN usuario ON mensagens.id_usuario = usuario.id
                                                 WHERE ((mensagens.id_usuario = :id_usuario AND mensagens.id_usuario2 = :id_usuario2)
                                                    OR (mensagens.id_usuario = :id_usuario2 AND mensagens.id_usuario2 = :id_usuario))
                                                 AND mensagens.status = 'ativo'
                                                 ORDER BY mensagens.data ASC");
            $stmtMensagens->execute([
                ':id_usuario' => $idUsuario,
                ':id_usuario2' => $id_usuario2
            ]);
            $mensagens = $stmtMensagens->fetchAll(PDO::FETCH_ASSOC);

            // Combine os dados em uma única resposta
            $response = [
                'usuario2' => $usuario2,
                'mensagens' => $mensagens
            ];

            if (is_array($mensagens)) {
                $from->send(json_encode($response));
            } else {
                $from->send(json_encode(['error' => 'Não foi possível recuperar as mensagens']));
            }
        }

        // Envio de mensagens
        if ($data['action'] === 'send_message') {
            $idUsuario = $from->user_id;
            $id_usuario2 = $from->id_usuario2;
            $mensagem = htmlspecialchars($data['mensagem'], ENT_QUOTES, 'UTF-8');

            $stmt = $this->db->prepare("INSERT INTO mensagens (id_usuario, mensagem, data, id_usuario2) VALUES (:id_usuario, :mensagem, NOW(), :id_usuario2)");
            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':mensagem' => $mensagem,
                ':id_usuario2' => $id_usuario2
            ]);

            $stmtMensagens = $this->db->prepare("SELECT mensagens.*, usuario.usuario, usuario.imagem 
                                                 FROM mensagens
                                                 JOIN usuario ON mensagens.id_usuario = usuario.id
                                                 WHERE mensagens.id_mensagem = :id_mensagem");
            $stmtMensagens->execute([':id_mensagem' => $this->db->lastInsertId()]);
            $novaMensagem = $stmtMensagens->fetch(PDO::FETCH_ASSOC);

            $response = [
                'usuario2' => null, // Pode ser preenchido com mais informações, se necessário
                'mensagens' => [$novaMensagem], // Envia a mensagem recém-enviada
            ];

            // Enviar a nova mensagem para todos os clientes conectados
            foreach ($this->clients as $client) {
                $client->send(json_encode($response));
            }
        }


        //excluir mensagens
        if ($data['action'] === 'exclude_message') {
            $id_mensagem = $data['id_mensagem'];

            // Marca a mensagem como inativa no banco de dados
            $stmt = $this->db->prepare("UPDATE mensagens SET status = 'inativo' WHERE id_mensagem = :id_mensagem");
            $stmt->execute([':id_mensagem' => $id_mensagem]);

            // Envia apenas o ID da mensagem excluída
            $response = [
                'action' => 'exclude_message',
                'id_mensagem' => $id_mensagem
            ];
            foreach ($this->clients as $client) {
                $client->send(json_encode($response));
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
