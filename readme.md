# Criptografia com Cifra de Vigenère

## 📋 Índice
1. [Introdução](#introdução)
2. [Conceitos Fundamentais](#conceitos-fundamentais)
3. [Criptografia](#criptografia)
4. [Descriptografia](#descriptografia)
5. [Força Bruta](#força-bruta)
6. [Função de Recuperação de Chave](#função-de-recuperação-de-chave)
7. [Exemplos Práticos](#exemplos-práticos)

---

## Introdução

A **Cifra de Vigenère** é um método de criptografia por substituição polialfabética clássico, desenvolvido no século XVI. Diferente da Cifra de César que usa um único deslocamento, Vigenère usa uma **sequência de deslocamentos** (chave), tornando o criptograma muito mais seguro.

### Características:
- ✅ Usa uma chave-palavra para criptografar
- ✅ Cada letra da mensagem é deslocada por um valor diferente
- ✅ Números são mantidos inalterados
- ✅ Susceptível a análise de frequência quando a chave é repetida

---

## Conceitos Fundamentais

### Alfabeto e Codificação
No algoritmo usamos as letras de 'a' a 'z' mapeadas para os valores 0-25:
```
a=0, b=1, c=2, ..., x=23, y=24, z=25
```

### Tabela de Vigenère
Uma tabela 26x26 onde:
- **Linha**: representa a letra da mensagem original
- **Coluna**: representa a posição da chave
- **Célula**: contém a letra criptografada

### Aritmética Modular
Todas as operações usam módulo 26 para garantir que o resultado esteja sempre no intervalo [0, 25]:
```
(a + b) mod 26 → resultado entre 0 e 25
```

---

## Criptografia

### Processo Passo-a-Passo

#### Etapa 1: Preparação do Texto
```
Mensagem original: "Olá Mundo 123"
↓
sanitizeText() - Remove acentos, caracteres especiais
↓
Texto limpo: "olamundo123"
↓
strtolower() - Converte para minúsculas
↓
Resultado: "olamundo123"
```

#### Etapa 2: Geração da Chave Expandida
A chave é repetida até ter o mesmo comprimento da mensagem (ignorando números).

```
Chave original: "segredo"
Comprimento da mensagem: 8 (olamundo)

Processo:
s e g r e d o s e g r e d o ...
↓
Chave expandida: "segredos"
```

**Cálculo:**
- Comprimento da chave: `keySize = strlen(key)`
- Enquanto comprimento da chave < comprimento da mensagem:
  - Acrescente character[i % keySize] à chave

#### Etapa 3: Criptografia Caractere por Caractere

Para cada caractere da mensagem:

```
Fórmula:
C = (P + K) mod 26

Onde:
C = letra cifrada (valor 0-25)
P = letra do texto simples (valor 0-25)
K = letra da chave (valor 0-25)
```

**Exemplo com a palavra "ola" e chave "seg":**

| Posição | Mensagem | ASCII | Valor(P) | Chave | Valor(K) | Cálculo    | (P+K)%26 | Letra Cifrada |
| ------- | -------- | ----- | -------- | ----- | -------- | ---------- | -------- | ------------- |
| 0       | o        | 111   | 14       | s     | 18       | (14+18)%26 | 6        | g             |
| 1       | l        | 108   | 11       | e     | 4        | (11+4)%26  | 15       | p             |
| 2       | a        | 97    | 0        | g     | 6        | (0+6)%26   | 6        | g             |

**Resultado da criptografia:** "gpg"

### Pseudo-código

```php
function encryptMessage(string $msg, string $key)
{
    // 1. Preparar texto
    $msg = sanitizeText($msg);
    $msg = strtolower($msg);
    
    // 2. Gerar chave expandida
    $key = generateKey($msg, strtolower($key));
    
    // 3. Criptografar
    $encrypted = '';
    for ($i = 0; $i < strlen($msg); $i++) {
        // Se é número, manter inalterado
        if (is_numeric($msg[$i])) {
            $encrypted .= $msg[$i];
            continue;
        }
        
        // Converter para valores 0-25
        $msgChar = ord($msg[$i]) - ord('a');    // Letra da mensagem
        $keyChar = ord($key[$i]) - ord('a');    // Letra da chave
        
        // Criptografia: (P + K) mod 26
        $mod = ($msgChar + $keyChar) % 26;
        
        // Converter de volta para letra
        $encrypted .= chr(ord('a') + $mod);
    }
    
    return $encrypted;
}
```

---

## Descriptografia

### Processo Passo-a-Passo

A descriptografia é o **inverso** da criptografia. Enquanto na criptografia **somamos** a chave, na descriptografia **subtraímos**.

#### Fórmula Base
```
P = (C - K) mod 26

Onde:
P = letra do texto original
C = letra cifrada
K = letra da chave
```

**Problema:** Subtração em aritmética modular pode gerar valores negativos.

#### Solução: Módulo com Segurança Negativa
```
P = ((C - K) % 26 + 26) % 26

O +26 garante que o resultado seja sempre positivo
```

**Exemplo prático:**

```
C = 5 (letra 'f')
K = 10 (letra 'k')

Cálculo incorreto:
(5 - 10) % 26 = -5 % 26 = -5 ❌

Cálculo correto:
((5 - 10) % 26 + 26) % 26 = (-5 + 26) % 26 = 21 % 26 = 21 ('v') ✓
```

### Exemplo Completo de Descriptografia

| Posição | Cifrado | Valor(C) | Chave | Valor(K) | Cálculo      | ((C-K)%26+26)%26 | Letra Original |
| ------- | ------- | -------- | ----- | -------- | ------------ | ---------------- | -------------- |
| 0       | g       | 6        | s     | 18       | (6-18)%26+26 | 14               | o              |
| 1       | p       | 15       | e     | 4        | (15-4)%26+26 | 11               | l              |
| 2       | g       | 6        | g     | 6        | (6-6)%26+26  | 0                | a              |

**Resultado:** "ola" ✓

### Pseudo-código

```php
function decryptMessage(string $cipherText, string $key)
{
    // 1. Preparar texto
    $cipherText = sanitizeText($cipherText);
    $cipherText = strtolower($cipherText);
    
    // 2. Gerar chave expandida
    $key = generateKey($cipherText, strtolower($key));
    
    // 3. Descriptografar
    $plainText = '';
    for ($i = 0; $i < strlen($cipherText); $i++) {
        // Se é número, manter inalterado
        if (is_numeric($cipherText[$i])) {
            $plainText .= $cipherText[$i];
            continue;
        }
        
        // Converter para valores 0-25
        $cipherChar = ord($cipherText[$i]) - ord('a');  // Letra cifrada
        $keyChar = ord($key[$i]) - ord('a');             // Letra da chave
        
        // Descriptografia com proteção contra negativos
        $mod = (($cipherChar - $keyChar) % 26 + 26) % 26;
        
        // Converter de volta para letra
        $plainText .= chr(ord('a') + $mod);
    }
    
    return $plainText;
}
```

---

## Força Bruta — Índice de Coincidência (IC)

### Objetivo
Descobrir automaticamente:
1. **Comprimento da chave**
2. **A chave em si**
3. **O texto original cifrado**

### Desafio
Se a mensagem é muito curta ou a chave é muito longa, o ataque torna-se impraticável por força bruta pura (26^n combinações).

### Solução: Análise Estatística

#### 1. Índice de Coincidência (IC)

**Conceito:**
O IC mede a probabilidade de dois caracteres escolhidos aleatoriamente em um texto serem iguais. Textos em linguagem natural têm IC distinto de textos aleatórios.

**Valores Típicos:**
- 📊 Português: **0.072** (7.2%)
- 🎲 Texto aleatório: **0.038** (3.8%)

**Fórmula:**
```
       Σ(ni × (ni - 1))
IC = ─────────────────
      N × (N - 1)

Onde:
ni = frequência da letra i no texto
N = comprimento total do texto
```

**Exemplo prático:**

Texto: "aaabbc" (N=6)
- 'a' aparece 3 vezes: 3 × (3-1) = 6
- 'b' aparece 2 vezes: 2 × (2-1) = 2
- 'c' aparece 1 vez: 1 × (1-1) = 0

```
IC = (6 + 2 + 0) / (6 × 5) = 8/30 ≈ 0.267
```

### Como Encontrar o Comprimento da Chave

#### Algoritmo: Utilizando IC para encontrar tamanho da chave

1. **Para cada tamanho k de 2 até 20:**
   - Dividir o texto cifrado em k "cosets" (subsequências)
   - Cada coset contém caracteres nas posições: i, i+k, i+2k, i+3k, ...
   - Calcular o IC de cada coset
   - Calcular a média dos ICs

2. **Análise dos ICs computados:**
   - Se IC médio ≈ 0.072 (IC do português)
   - Então k provavelmente é o comprimento da chave

3. **Filtrar candidatos:**
   - Valores de IC entre 0.060 e 0.080 são fortes candidatos
   - Escolher o que mais se aproxima de 0.072

### Exemplo Prático: Encontrando Comprimento da Chave

```
Texto cifrado: "gpgzlaoazxxz" (comprimento 12)
Chave real: "seg" (comprimento 3)

Teste com k=3:
Coset 0 (posição 0, 3, 6, 9):  "gpaz"
Coset 1 (posição 1, 4, 7, 10): "pzax"
Coset 2 (posição 2, 5, 8, 11): "gaoz"

Se calcularmos o IC de cada coset usando frequência de letras,
um deles terá IC próximo a 0.072 → chave com comprimento 3!
```

### Pseudo-código: Encontrar Comprimento

```php
function findKeyLength(string $cipherText)
{
    $icKeys = [];
    
    for ($k = 2; $k <= 20; $k++) {
        // Dividir em k cosets
        $cosets = splitInCosets($cipherText, $k, true);
        
        // Calcular IC de cada coset
        $icValues = [];
        foreach ($cosets as $coset) {
            $ic = calculateIndexCoincidence($coset);
            $icValues[] = $ic;
        }
        
        // IC médio para este k
        $icKeys[$k] = array_sum($icValues) / count($icValues);
    }
    
    // Encontrar k com IC mais próximo de IC_PORTUGUESE (0.072)
    $bestK = null;
    $bestDifference = PHP_FLOAT_MAX;
    
    foreach ($icKeys as $k => $ic) {
        $diff = abs($ic - 0.072);
        if ($diff < $bestDifference) {
            $bestDifference = $diff;
            $bestK = $k;
        }
    }
    
    return $bestK;
}
```

---

## Função de Recuperação de Chave

### Objetivo
Uma vez conhecendo o comprimento k da chave, descobrir qual é a chave.

### Técnica: Teste de Qui-Quadrado ($\chi^2$)

**Conceito:**
Compara a distribuição de frequência observada com a frequência esperada em português.

**Fórmula:**
```
         (Obsi - Espi)²
χ² = Σ ──────────────
            Espi

Onde:
Obsi = frequência observada da letra i
Espi = frequência esperada da letra i no português
```

**Interpretação:**
- Quanto **MENOR** o valor de $\chi^2$, melhor o ajuste
- Lower $\chi^2$ significa que a distribuição observada corresponde bem ao português

### Algoritmo de Recuperação da Chave

1. **Para cada coset i (0 até k-1):**
   - Para cada shift possível (0 até 25):
     - Aplicar o shift ao coset (descriptografar com essa letra)
     - Calcular a frequência de cada letra após o shift
     - Calcular $\chi^2$ comparando com frequência esperada do português
   - A letra de shift com **menor $\chi^2$** é a letra correta da chave

2. **Concatenar** todas as letras encontradas para formar a chave

### Exemplo Prático de Recuperação

```
Coset 0 (do texto cifrado): "gpgzlao"

Testando shifts (0-25):
  Shift 0 (a): "gpgzlao" → χ² = 45.3
  Shift 1 (b): "hqhambp" → χ² = 52.1
  ...
  Shift 18 (s): "olamundo_" → χ² = 3.2 ✓ (MELHOR!)
  ...
  Shift 25 (z): "ututskj" → χ² = 48.9

Resultado: Letra 's' tem o menor χ²
Portanto, primeira letra da chave = 's'
```

### Pseudo-código

```php
function recoveryKeyword(int $probableKeyLength, string $cipherText)
{
    // Frequência esperada das letras em português
    $freqPortuguese = [
        'a' => 0.1463, 'b' => 0.0104, 'c' => 0.0388, ...
    ];
    
    // Dividir em cosets
    $cosets = splitInCosets($cipherText, $probableKeyLength, true);
    
    $keyword = "";
    
    foreach ($cosets as $index => $coset) {
        $chiSquared = [];
        
        // Testar cada shift (0-25)
        for ($shift = 0; $shift < 26; $shift++) {
            $observedFreq = [];
            
            // Aplicar shift e contar frequências
            foreach ($coset as $char) {
                $shiftedChar = chr((ord($char) - ord('a') + $shift) % 26 + ord('a'));
                $observedFreq[$shiftedChar]++;
            }
            
            // Calcular χ²
            $chi2 = 0;
            foreach ($observedFreq as $char => $count) {
                $expected = strlen($coset) * $freqPortuguese[$char];
                $chi2 += (($count - $expected) ** 2) / $expected;
            }
            
            // Guardar χ² para este shift
            $chiSquared[chr(ord('a') + $shift)] = $chi2;
        }
        
        // Encontrar a letra com menor χ²
        asort($chiSquared);
        $keyword .= key($chiSquared); // Primeira chave tem menor χ²
    }
    
    return $keyword;
}
```

---

## Exemplos Práticos

### Exemplo 1: Criptografia Simples

**Entrada:**
- Texto: "python"
- Chave: "key"

**Processo:**

```
1. Preparar:
   Texto: "python" → "python"
   Chave: "key"

2. Expandir chave para comprimento 6:
   k e y k e y

3. Criptografar letra por letra:
   p (15) + k (10) = 25 % 26 = 25 → 'z'
   y (24) + e (4) = 28 % 26 = 2 → 'c'
   t (19) + y (24) = 43 % 26 = 17 → 'r'
   h (7) + k (10) = 17 % 26 = 17 → 'r'
   o (14) + e (4) = 18 % 26 = 18 → 's'
   n (13) + y (24) = 37 % 26 = 11 → 'l'

Resultado: "zcrssl"
```

### Exemplo 2: Descriptografia com Chave Conhecida

**Entrada:**
- Cifrado: "zcrssl"
- Chave: "key"

**Processo:**

```
1. Preparar: "zcrssl", "key" expandida para "keykey"

2. Descriptografar:
   z (25) - k (10) = 15 % 26 = 15 → 'p'
   c (2) - e (4) = -2, (-2 + 26) % 26 = 24 → 'y'
   r (17) - y (24) = -7, (-7 + 26) % 26 = 19 → 't'
   r (17) - k (10) = 7 % 26 = 7 → 'h'
   s (18) - e (4) = 14 % 26 = 14 → 'o'
   l (11) - y (24) = -13, (-13 + 26) % 26 = 13 → 'n'

Resultado: "python" ✓
```

### Exemplo 3: Força Bruta — Descobrir Chave Desconhecida

**Entrada:**
- Cifrado: "zcrssl" (sem conhecer a chave)
- Texto suficientemente longo para análise estatística

**Processo:**

```
1. Encontrar comprimento da chave:
   Teste IC para k=1: 0.045 (não é português)
   Teste IC para k=2: 0.038 (aleatório)
   Teste IC para k=3: 0.068 (próximo a 0.072!) ✓
   
   Conclusão: chave tem comprimento 3

2. Recuperar chave usando χ²:
   Coset 0 (pos 0,3): "zr"
     Shift 10 (k): aplicando → "py" (χ² mínimo) → letra 'k'
   
   Coset 1 (pos 1,4): "cr"
     Shift 4 (e): aplicando → "yt" (χ² mínimo) → letra 'e'
   
   Coset 2 (pos 2,5): "sl"
     Shift 24 (y): aplicando → "on" (χ² mínimo) → letra 'y'
   
   Chave encontrada: "key"

3. Descriptografar com chave descoberta:
   Resultado: "python"
```

---

## Estrutura de Funções Utilizadas

### 1. `sanitizeText(string $text)`
Remove acentos, caracteres especiais e mantém apenas letras e números.

### 2. `generateKey(string $msg, string $key)`
Expande a chave repetindo-a até ter o mesmo comprimento da mensagem.

### 3. `encryptMessage(string $msg, string $key)`
Criptografa uma mensagem usando a Cifra de Vigenère.

### 4. `decryptMessage(string $cipherText, string $key)`
Descriptografa um texto usando a chave conhecida.

### 5. `calculateIndexCoincidence(array $letterFreq, int $length)`
Calcula o Índice de Coincidência para análise estatística.

### 6. `splitInCosets(string $text, int $length)`
Divide o texto em cosets para análise de frequência.

### 7. `shiftCipherText(string $cipherText)`
Testa múltiplos comprimentos de chave calculando IC para cada um.

### 8. `findKeyLength(string $cipherText)`
Determina automaticamente o comprimento da chave usando análise de IC.

### 9. `calculateQuiQuad(array $letterFreq, array $expectedFreq)`
Calcula o valor do teste de Qui-Quadrado para comparação de distribuições.

### 10. `recoveryKeyword(int $keyLength, string $cipherText)`
Recupera a chave usando teste de Qui-Quadrado em cada coset.

---

## Limitações e Considerações de Segurança

⚠️ **AVISOS IMPORTANTES:**

1. **Texto Curto:** Com poucos caracteres, análise estatística falha
2. **Chave Longa:** Se chave ≥ comprimento do texto, força bruta torna-se impraticável
3. **Múltiplas Soluções:** Em alguns casos, múltiplos comprimentos de chave podem parecer válidos
4. **Padrões Repetidos:** Mensagens com padrões óbvios são mais fáceis de quebrar
5. **Uso Moderno:** Vigenère é INSEGURO para criptografia moderna - use AES, RSA, etc.

---

## Conclusão

A Cifra de Vigenère é um exercício educacional excelente que demonstra como criptografia funciona. Através de conceitos como:
- ✓ Operações modulares
- ✓ Análise estatística
- ✓ Frequência de letras
- ✓ Métodos de força bruta inteligente

Podemos entender princípios que ainda são relevantes na criptografia moderna.
