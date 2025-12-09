<?php
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/supabase.php'; // aqui já temos as funções sb_request e sb_auth_admin_delete_user

// Lê JSON
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['user_id'])) {
    http_response_code(400);
    echo json_encode([
        'ok'      => false,
        'message' => 'Usuário não informado.'
    ]);
    exit;
}

$userId = $data['user_id'];

// ==========================
// 1) APAGAR DADOS DO BANCO
// ==========================
try {
    // pets (campo certo é owner_id, não tutor_id)
    [$stPets, $resPets] = sb_request(
        'DELETE',
        '/rest/v1/pets?owner_id=eq.' . $userId
    );

    if ($stPets >= 300 && !empty($resPets['error'])) {
        throw new Exception('Erro ao apagar pets: ' . json_encode($resPets));
    }

    // listings (do anfitrião)
    [$stListings, $resListings] = sb_request(
        'DELETE',
        '/rest/v1/listings?host_id=eq.' . $userId
    );
    if ($stListings >= 300 && !empty($resListings['error'])) {
        throw new Exception('Erro ao apagar listings: ' . json_encode($resListings));
    }

    // mensagens
    [$stMsgs, $resMsgs] = sb_request(
        'DELETE',
        '/rest/v1/messages?sender_id=eq.' . $userId
    );
    if ($stMsgs >= 300 && !empty($resMsgs['error'])) {
        throw new Exception('Erro ao apagar mensagens: ' . json_encode($resMsgs));
    }

    // chats (como tutor OU host)
    [$stChats, $resChats] = sb_request(
        'DELETE',
        '/rest/v1/chats?or=(tutor_id.eq.' . $userId . ',host_id.eq.' . $userId . ')'
    );
    if ($stChats >= 300 && !empty($resChats['error'])) {
        throw new Exception('Erro ao apagar chats: ' . json_encode($resChats));
    }

    // hosts
    [$stHosts, $resHosts] = sb_request(
        'DELETE',
        '/rest/v1/hosts?id=eq.' . $userId
    );
    if ($stHosts >= 300 && !empty($resHosts['error'])) {
        throw new Exception('Erro ao apagar host: ' . json_encode($resHosts));
    }

    // profiles
    [$stProfiles, $resProfiles] = sb_request(
        'DELETE',
        '/rest/v1/profiles?id=eq.' . $userId
    );
    if ($stProfiles >= 300 && !empty($resProfiles['error'])) {
        throw new Exception('Erro ao apagar profile: ' . json_encode($resProfiles));
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok'      => false,
        'message' => 'Erro ao excluir dados: ' . $e->getMessage()
    ]);
    exit;
}

// ==========================
// 2) EXCLUIR DO AUTH (ADMIN)
// ==========================
list($status, $body) = sb_auth_admin_delete_user($userId);

if ($status >= 200 && $status < 300) {
    echo json_encode([
        'ok'      => true,
        'message' => 'Conta excluída com sucesso'
    ]);
    exit;
} else {
    echo json_encode([
        'ok'      => false,
        'message' => 'Dados apagados, mas falhou ao excluir usuário de autenticação: ' . json_encode($body)
    ]);
    exit;
}
