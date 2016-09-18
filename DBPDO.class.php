<?php
/**
 * Created by PhpStorm.
 * User: vitor
 * Date: 17/09/16
 * Time: 19:29
 *
 * Classe usada para conexão com o banco de dados usando PDO
 * Validação de SQL injection automática na própria classe.
 *
 *
 */
class DBPDO
{
    private $connection;
    private $host;
    private $password;
    private $user;
    private $dbName;

    /**
     * DBPDO constructor.
     *
     * Private para usar o Design Pattern Singleton.
     *
     * @param null $host -> Host de conexao
     * @param null $dbName -> Nome do banco de dados
     * @param null $user -> Usuario do banco
     * @param null $pass -> Senha do banco
     */
    public function __construct($host,$dbName,$user,$pass)
    {
        if ($this->paramsIsNotNull(func_get_args()))
        {
            $this->connection = new PDO("mysql:host={$host};dbname={$dbName}",$user,$pass);
            $this->host       = $host;
            $this->password   = $pass;
            $this->dbName     = $dbName;
            $this->user       = $user;
            return $this->connection;
        }

        return false;
    }

    /**
     * Verifica se todos os parametros passados por parametro nao estao nulos
     *
     * @param $args -> Argumentos que devem ser avaliados
     * @return bool -> true|false
     */
    public function paramsIsNotNull($args)
    {
        if ($args)
        {
            foreach ($args as $arg)
            {
                if (empty($arg))
                {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Executa uma query no banco
     *
     * @param $query
     * @return bool|PDOStatement
     */
    public function execute($query)
    {
        if (!empty($query))
        {
            $stm = $this->connection->query($query);
            return $stm;
        }

        return false;
    }

    /**
     * Faz um insert no banco de dados
     *
     * @param $tabela
     * @param $dados
     */
    public function insert($table,$data)
    {
        if ($this->paramsIsNotNull(func_get_args()))
        {
            $prepare           = $this->mountPatternPrepare($data);
            $columns           = implode(array_keys((array)$data),',');
            $values            = implode($prepare,',');
            $queryPrepare      = "INSERT INTO {$table}($columns) VALUES({$values})";
            $connectionPrepare = $this->connection->prepare($queryPrepare);
            $connectionPrepare = $this->replacePatternBind($data,$connectionPrepare,$table);
            return $connectionPrepare->execute();
        }
    }

    /**
     * Monta o padrao de substituicao do PDO
     *  para operaçoes esta se usando o padrao ':nome_da_coluna' ou ':nome_do_valor'
     *
     * @param $data -> Dados que deseja aplicar o padrao
     * @return array|bool
     */
    public function mountPatternPrepare($data)
    {
        if ($this->paramsIsNotNull(func_get_args()) AND is_object($data))
        {
            foreach ($data as $column => $dat)
            {
                $prepare[] = ":{$column}";
            }

            return $prepare;
        }

        return false;
    }

    /**
     * Substitui o padrão do SQL pelos valores do array passado.
     *  O valores e os padroes tem que terem o mesmo nome.
     *
     * @param $data
     * @param PDOStatement $connectionPrepare
     */
    public function replacePatternBind($data,PDOStatement $connectionPrepare,$table)
    {
        if ($this->paramsIsNotNull(func_get_args()))
        {
            foreach ($data as $column => $dat)
            {
                $connectionPrepare->bindValue(":{$column}",$dat,$this->getTypeColumn($table,$column));
            }

            return $connectionPrepare;
        }

        return false;
    }

    /**
     * Retorna o tipo da coluna no banco de dados
     *
     * @param $table
     * @param $column
     * @return mixed
     */
    public function getTypeColumn($table,$column)
    {
        if (!empty($table))
        {
            $query = "SELECT * 
                          FROM information_schema.columns 
                      WHERE table_schema = '{$this->dbName}' 
                        AND table_name = '{$table}' 
                        AND COLUMN_NAME = '{$column}';";
            $data = $this->connection->query($query);
            return $this->rankColumn($data->fetch(PDO::FETCH_ASSOC)['DATA_TYPE']);
        }
    }

    /**
     * Classifica o tipo de filtro que deve ser aplicado na função bind()
     *  conforme o tipo de dados da coluna.
     *
     * @param $type
     * @return int|null
     */
    public function rankColumn($type)
    {
        switch (strtolower($type))
        {
            case 'varchar' :
                $return = PDO::PARAM_STR;
            case 'int' :
                $return = PDO::PARAM_INT;
            default :
                $return = null;
        }

        return $return;
    }
}