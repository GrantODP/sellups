<?php
require_once './backend/db/Database.php';
require_once './backend/core/Result.php';;
require_once './backend/util/Util.php';





class Message
{

  public int $sender;
  public int $receiver;
  public string $message;
  public string $time;
  public bool $is_read;

  public function __construct(array $data)
  {
    $this->sender = $data['sender_id'] ?? 0;
    $this->receiver = $data['receiver_id'] ?? $data['receiver'] ?? 0;
    $this->message = $data['message'] ?? '';
    $this->time = $data['time'] ?? date('Y-m-d H:i:s');
    $this->is_read = $data['is_read'] ?? false;
  }
  public static function get_conversations($user_id): ?array
  {
    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("SELECT * FROM conversations WHERE user_id1 = :user_id OR user_id2 = :user_id ");
      $stmt->bindValue(':user_id', $user_id);
      $stmt->execute();

      $convos = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if ($convos) {
        return  $convos;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public static function get_messages_by_convo($conversation_id): ?array
  {
    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("SELECT * FROM messages WHERE conversation_id = :id");
      $stmt->bindValue(':id', $conversation_id);
      $stmt->execute();

      $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
      if ($messages) {
        return  $messages;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }

  public function post(): Result
  {
    try {
      Database::connect();

      $convo_id = self::get_conversation($this->sender, $this->receiver);
      if ($convo_id === null) {
        $convo_id = self::make_conversation($this->sender, $this->receiver);
      }
      $db = Database::db();
      $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, message, conversation_id) VALUES (:sid, :rid, :message, :cid)");

      $stmt->bindValue(':sid', $this->sender);
      $stmt->bindValue(':rid', $this->receiver);
      $stmt->bindValue(':message', $this->message);
      $stmt->bindValue(':cid', $convo_id);
      $stmt->execute();
    } catch (PDOException $e) {
      return Result::Err("Error: " . $e->getMessage());
    }

    return  Result::Ok(null);
  }

  private static function get_conversation($user1, $user2): ?int
  { {
      try {
        Database::connect();

        $db = Database::db();
        $stmt = $db->prepare("SELECT conversation_id FROM conversations
          WHERE (user1_id = LEAST(:user1, :user2) AND user2_id = GREATEST(:user1, :user2))");
        $stmt->bindValue(':user1', $user1);
        $stmt->bindValue(':user2', $user2);
        $stmt->execute();

        $id = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($id) {
          return  $id['conversation_id'];
        }
      } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
      }
      return null;
    }
  }
  private static function make_conversation($user1, $user2): ?int
  {
    try {
      Database::connect();

      $db = Database::db();
      $stmt = $db->prepare("INSERT INTO conversations (user1_id, user2_id)
          VALUES (LEAST(:user1, :user2), GREATEST(:user1, :user2))");
      $stmt->bindValue(':user1', $user1);
      $stmt->bindValue(':user2', $user2);
      $stmt->execute();

      $id = $db->lastInsertId();
      if ($id) {
        return  $id;
      }
    } catch (PDOException $e) {
      echo "Error: " . $e->getMessage();
    }
    return null;
  }
}
