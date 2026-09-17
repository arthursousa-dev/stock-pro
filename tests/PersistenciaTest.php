<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Config\Database;
use PDO;

final class PersistenciaTest extends TestCase
{
    private PDO $pdo;
    private string $codigoTeste = 'TESTE-INSERIR-001';

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->limpar();
    }

    protected function tearDown(): void
    {
        $this->limpar();
    }

    private function limpar(): void
    {
        $this->pdo->prepare('DELETE FROM produtos WHERE codigo = :codigo')->execute([':codigo' => $this->codigoTeste]);
    }

    public function test_inserirRegistro_nao_calcula_id_em_php_e_devolve_o_gerado_pelo_serial(): void
    {
        $id = inserirRegistro('produtos', [
            'codigo' => $this->codigoTeste,
            'nome' => 'Produto de Teste',
            'categoria' => 'Testes',
            'preco_custo' => 10.00,
            'preco_venda' => 15.00,
            'estoque' => 5,
            'estoque_minimo' => 1,
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $linha = $this->pdo->prepare('SELECT * FROM produtos WHERE id = :id');
        $linha->execute([':id' => $id]);
        $produto = $linha->fetch();

        $this->assertSame($this->codigoTeste, $produto['codigo']);
    }

    public function test_dois_inserts_seguidos_geram_ids_diferentes(): void
    {
        $id1 = inserirRegistro('produtos', [
            'codigo' => $this->codigoTeste, 'nome' => 'A', 'categoria' => 'Testes',
            'preco_custo' => 1, 'preco_venda' => 2, 'estoque' => 0, 'estoque_minimo' => 0,
        ]);
        $this->pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute([':id' => $id1]);

        $id2 = inserirRegistro('produtos', [
            'codigo' => $this->codigoTeste, 'nome' => 'B', 'categoria' => 'Testes',
            'preco_custo' => 1, 'preco_venda' => 2, 'estoque' => 0, 'estoque_minimo' => 0,
        ]);

        $this->assertNotSame($id1, $id2, 'o SERIAL nunca deveria repetir um id, mesmo após exclusão');

        $this->pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute([':id' => $id2]);
    }

    public function test_lerJson_devolve_produto_inserido_direto_no_banco(): void
    {
        inserirRegistro('produtos', [
            'codigo' => $this->codigoTeste, 'nome' => 'Produto de Teste', 'categoria' => 'Testes',
            'preco_custo' => 10, 'preco_venda' => 15, 'estoque' => 5, 'estoque_minimo' => 1,
        ]);

        $db = ler_json('db_produtos.json');
        $encontrado = null;
        foreach ($db['produtos'] as $p) {
            if ($p['codigo'] === $this->codigoTeste) {
                $encontrado = $p;
            }
        }

        $this->assertNotNull($encontrado);
        $this->assertSame('Produto de Teste', $encontrado['nome']);
    }

    public function test_arquivo_desconhecido_devolve_array_vazio(): void
    {
        $this->assertSame([], ler_json('arquivo_que_nao_existe.json'));
    }
}
