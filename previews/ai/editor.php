<?php
declare(strict_types=1);

require_once __DIR__ . '/adapter.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

ai_check_access();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = (string)($_GET['action'] ?? 'run');

try {
    if ($method === 'GET' && $action === 'config') {
        echo json_encode(['ok' => true] + ai_config_public());
        exit;
    }

    if ($method === 'GET' && $action === 'history') {
        $scope = [
            'kind'       => (string)($_GET['kind'] ?? ''),
            'name'       => (string)($_GET['name'] ?? ''),
            'collection' => (string)($_GET['collection'] ?? ''),
        ];
        $entries = ai_audit_tail(50, $scope['name'] !== '' ? $scope : null);
        echo json_encode(['ok' => true, 'entries' => $entries]);
        exit;
    }

    if ($method === 'POST') {
        $raw = file_get_contents('php://input') ?: '';
        $req = ($raw !== '' && $raw[0] === '{') ? (json_decode($raw, true) ?: []) : $_POST;

        if ($action === 'revert') {
            $scope = ai_resolve_scope((array)($req['scope'] ?? []));
            $r = ai_revert_latest($scope);
            ai_audit_append([
                'kind'    => 'revert',
                'scope'   => ['kind' => $scope['kind'], 'name' => $scope['name'], 'collection' => $scope['collection']],
                'runId'   => $r['runId'],
                'restored' => $r['restored'],
            ]);
            echo json_encode(['ok' => true] + $r);
            exit;
        }

        // default action: run
        $result = runAiEditorRequest($req);
        echo json_encode($result);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unsupported action: ' . $action]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
