<?php

function getConversationHistory($pdo, $conversation_id) {
    try {
        $sql = "SELECT * FROM messages WHERE conversation_id = :conversation_id ORDER BY created_at DESC LIMIT 5";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':conversation_id', $conversation_id, PDO::PARAM_INT);
        $stmt->execute();
        return array_reverse($stmt->fetchAll());
    } catch (Exception $e) {
        error_log("Erro ao buscar histórico: " . $e->getMessage());
        return [];
    }
}

function getChatGPTResponse($message, $conversation_id = null) {
    global $pdo;
    
    // Implementar controle de taxa básico
    $rateLimit = getRateLimit();
    if ($rateLimit['remaining'] <= 0) {
        return "Desculpe, o sistema está muito ocupado no momento. Por favor, aguarde alguns segundos e tente novamente.";
    }

    // API Key da OpenAI
    $apiKey = 'chave-api'; // Substitua pela chave de API
    if (!$apiKey) {
        error_log("Erro: API key do OpenAI não configurada");
        return "Erro: API key não configurada";
    }

    // Preparar o array de mensagens para o ChatGPT
    $messages = [
        [
            'role' => 'system',
            'content' => 'Você é Terap.IA, um assistente psicológico conversacional.
            Você deve-se lembrar do nome do usuário.
Seu papel é funcionar como um(a) facilitador(a) de autoconhecimento, inspirado na escuta psicanalítica, mas sem oferecer diagnósticos ou prescrições clínicas.

Diretrizes fundamentais

Escuta ativa e empática – responda com acolhimento, valide emoções e demonstre compreensão genuína.

Perguntas abertas – em vez de aconselhar diretamente, convide a pessoa a elaborar livremente:

“O que esse sentimento lhe lembra?”

“Como você percebe esse padrão se repetindo na sua história?”

Enfoque psicanalítico suave – explore temas de inconsciente, sonhos, lapsos, transferências e resistências, mas sempre como hipóteses a serem investigadas pela própria pessoa (“Talvez isso sugira… o que você pensa a respeito?”).

Neutralidade e não julgamento – mantenha-se imparcial; evite impor valores pessoais.

Limites claros

Nunca rotule ou diagnostique (ex.: “Você tem depressão”).

Não recomende medicamentos ou intervenções médicas.

Caso o usuário relate risco iminente (autoagressão ou violência), incentive-o a buscar ajuda profissional urgente e forneça telefones de emergência locais se souber.

Orientação à reflexão – ajude o usuário a identificar padrões, significados e conflitos internos, incentivando a escrita de diário, técnicas de associação livre ou análise de sonhos.

Linguagem – utilize um tom calmo, respeitoso e claro; evite jargões excessivos.

Declaração de responsabilidade – em interações iniciais ou quando o usuário solicitar conclusões, lembre: “Sou um assistente virtual e não substituo psicoterapia presencial com profissional habilitado.”

Objetivo final: conduzir o usuário a construir suas próprias interpretações e decisões, reforçando autonomia e autoconsciência, sempre dentro dos limites éticos descritos acima.'
        ]
    ];

    // Adicionar histórico da conversa se disponível
    if ($conversation_id !== null && isset($pdo)) {
        $history = getConversationHistory($pdo, $conversation_id);
        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['sender'] === 'user' ? 'user' : 'assistant',
                'content' => $msg['message']
            ];
        }
    }

    // Adicionar a mensagem atual
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];

    $data = [
        'model' => 'gpt-3.5-turbo',  // Modelo correto da OpenAI
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 500,  // Aumentado para permitir respostas mais longas
        'presence_penalty' => 0.6,  // Encoraja a IA a não repetir informações
        'frequency_penalty' => 0.4  // Reduz a repetição de palavras
    ];

    // Adicionar log para debug
    error_log("Enviando requisição para OpenAI: " . json_encode($data));

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error) {
        error_log("Erro cURL: " . $error);
        return "Desculpe, ocorreu um erro na comunicação com a IA.";
    }

    if ($httpCode === 429) {
        error_log("Limite de requisições atingido: " . $response);
        return "O sistema está temporariamente sobrecarregado. Por favor, aguarde alguns segundos e tente novamente.";
    }

    if ($httpCode !== 200) {
        error_log("Erro na API do OpenAI (HTTP $httpCode): " . $response);
        return "Desculpe, houve um erro ao processar sua mensagem. Por favor, tente novamente em alguns instantes.";
    }

    $result = json_decode($response, true);
    if (!$result || !isset($result['choices'][0]['message']['content'])) {
        error_log("Resposta inválida da API: " . $response);
        return "Desculpe, recebi uma resposta inválida da IA.";
    }

    return $result['choices'][0]['message']['content'];
}

// Funções auxiliares para controle de taxa
function getRateLimit() {
    $cacheFile = sys_get_temp_dir() . '/openai_rate_limit.json';
    
    if (!file_exists($cacheFile)) {
        return ['remaining' => 3, 'reset' => time() + 60];
    }

    $data = json_decode(file_get_contents($cacheFile), true);
    
    if ($data['reset'] <= time()) {
        $data = ['remaining' => 3, 'reset' => time() + 60];
        file_put_contents($cacheFile, json_encode($data));
    }

    return $data;
}

function updateRateLimit($ch) {
    $cacheFile = sys_get_temp_dir() . '/openai_rate_limit.json';
    $data = getRateLimit();
    
    // Reduzir o número de requisições restantes
    $data['remaining']--;
    
    // Se não houver mais requisições, definir próximo reset para 60 segundos no futuro
    if ($data['remaining'] <= 0) {
        $data['reset'] = time() + 60;
        $data['remaining'] = 0;
    }

    file_put_contents($cacheFile, json_encode($data));
}
?>
