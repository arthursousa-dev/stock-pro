<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Config\Database;
use PDO;

final class AutenticacaoTest extends TestCase
{
    private PDO $pdo;
    private string $email = 'teste.autenticacao@stockpro.local';
    private string $senha = 'senhaCorreta123';

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->pdo->prepare('DELETE FROM usuarios WHERE email = :email')->execute([':email' => $this->email]);

        $this->pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :senha, :perfil)'
        )->execute([
            ':nome' => 'Usuário de Teste',
            ':email' => $this->email,
            ':senha' => password_hash($this->senha, PASSWORD_DEFAULT),
            ':perfil' => 'admin',
        ]);
    }

    protected function tearDown(): void
    {
        $this->pdo->prepare('DELETE FROM usuarios WHERE email = :email')->execute([':email' => $this->email]);
    }

    public function test_login_com_credenciais_corretas_retorna_usuario(): void
    {
        $resultado = autenticar($this->email, $this->senha);

        $this->assertIsArray($resultado);
        $this->assertSame('Usuário de Teste', $resultado['nome']);
        $this->assertSame('admin', $resultado['perfil']);
    }

    public function test_login_com_senha_errada_retorna_false(): void
    {
        $this->assertFalse(autenticar($this->email, 'senhaErrada'));
    }

    public function test_login_com_email_inexistente_retorna_false(): void
    {
        $this->assertFalse(autenticar('nao.existe@stockpro.local', 'qualquer'));
    }

    public function test_conta_bloqueia_apos_cinco_tentativas_incorretas(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->assertFalse(autenticar($this->email, 'senhaErrada'));
        }
        autenticar($this->email, 'senhaErrada'); // 5ª tentativa: bloqueia

        $resultado = autenticar($this->email, $this->senha);
        $this->assertFalse($resultado, 'conta deveria estar bloqueada após 5 tentativas incorretas');
    }

    public function test_login_correto_zera_contador_de_tentativas(): void
    {
        autenticar($this->email, 'senhaErrada');
        autenticar($this->email, 'senhaErrada');
        $this->assertIsArray(autenticar($this->email, $this->senha));

        $linha = $this->pdo->prepare('SELECT tentativas_login FROM usuarios WHERE email = :email');
        $linha->execute([':email' => $this->email]);
        $this->assertSame(0, (int) $linha->fetchColumn());
    }
}
