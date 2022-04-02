<?php

namespace Microestc\OscarDB\ACI_PDO;

class ACI extends \PDO
{
    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string Username for connection
     */
    protected $username;

    /**
     * @var string Password for connection
     */
    protected $password;

    /**
     * @var resource Oscar Database connection
     */
    /**
     * @var bool|resource|string
     */
    protected $conn;

    /**
     * @var array Database connection attributes
     */
    protected $attributes = [\PDO::ATTR_AUTOCOMMIT => 1,
        \PDO::ATTR_ERRMODE => 0,
        \PDO::ATTR_CASE => 0,
        \PDO::ATTR_OSCAR_NULLS => 0,
    ];

    /**
     * @var bool Tracks if currently in a transaction
     */
    protected $transaction = false;

    /**
     * @var int Mode for executing on Database Connection
     */
    protected $mode = \ACI_COMMIT_ON_SUCCESS;

    /**
     * @var array PDO errorInfo array
     */
    protected $error = [0 => '', 1 => null, 2 => null];

    /**
     * @var string SQL statement to be run
     */
    public $queryString = '';

    /**
     * @var ACIStatement Statement object
     */
    protected $stmt = null;

    /**
     * @var bool Set this to FALSE to turn debug output off or TRUE to turn it on.
     */
    protected $internalDebug = false;

    /**
     * Constructor.
     *
     * @param string $dsn DSN string to connect to database
     * @param string $username Username of creditial to login to database
     * @param string $password Password of creditial to login to database
     * @param array $driver_options Options for the connection handle
     *
     * @throws ACIException if connection fails
     */
    public function __construct($dsn, $username, $password, $driver_options = [])
    {
        $this->dsn = $dsn;
        $this->username = $username;
        $this->password = $password;
        $this->attributes = $driver_options + $this->attributes;

        if ($this->getAttribute(\PDO::ATTR_PERSISTENT)) {
            $this->conn = new PDO($dsn, $username, $password, array(PDO::ATTR_PERSISTENT => true));
        } else {
            $this->conn = new PDO($dsn, $username, $password);
        }

        //Check if connection was successful
        if (! $this->conn) {
            throw new ACIException($this->setErrorInfo('08006'));
        }
    }

    /**
     * Destructor - Checks for an aci resource and frees the resource if needed.
     */
    public function __destruct()
    {
        if (strtolower(get_resource_type($this->conn)) == 'aci') {
            // $this->conn->close();
        }
    }

    /**
     * Initiates a transaction.
     *
     * @throws ACIException If already in a transaction
     *
     * @return bool Returns TRUE on success
     */
    public function beginTransaction()
    {
        if ($this->inTransaction()) {
            throw new ACIException($this->setErrorInfo('25000', '9999', 'Already in a transaction'));
        }

        $this->conn->beginTransaction();

        $this->transaction = $this->setExecuteMode(\ACI_NO_AUTO_COMMIT);

        return true;
    }

    /**
     * Commits a transaction.
     *
     * @throws ACIException If aci_commit fails
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function commit()
    {
        if ($this->inTransaction()) {
            $r = $this->conn->commit();
            if (! $r) {
                throw new ACIException('08007');
            }
            $this->transaction = ! $this->flipExecuteMode();

            return true;
        }

        return false;
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the database handle.
     *
     * @return mixed Returns SQLSTATE if available or null
     */
    public function errorCode()
    {
        if (! empty($this->error[0])) {
            return $this->error[0];
        }

        return;
    }

    /**
     * Fetch extended error information associated with the last operation on the database handle.
     *
     * @return array Array of error information about the last operation performed
     */
    public function errorInfo()
    {
        return $this->error;
    }

    /**
     * Execute an SQL statement and return the number of affected rows.
     *
     * @param  string $statement The SQL statement to prepare and execute.
     *
     * @return int Returns the number of rows that were modified or deleted by the statement
     */
    public function exec($statement)
    {
        $this->prepare($statement);

        $result = $this->stmt->execute();

        if (! $result) {
            return false;
        }

        return $this->stmt->rowCount();
    }

    /**
     * Retrieve a database connection attribute.
     *
     * @param int $attribute One of the PDO::ATTR_* constants.
     *
     * @return mixed The value of the requested PDO attribute or null if it does not exist.
     */
    public function getAttribute($attribute)
    {
        if (isset($this->attributes[$attribute])) {
            return $this->attributes[$attribute];
        }

        return;
    }

    /**
     * Checks if inside a transaction.
     *
     * @return bool Returns TRUE if a transaction is currently active, and FALSE if not.
     */
    public function inTransaction()
    {
        return $this->transaction;
    }

    /**
     * Returns the ID of the last inserted row or sequence value.
     *
     * @throws ACIException This feature is not supported
     */
    public function lastInsertId($name = null)
    {
        return $this->query("select last_insert_id()");
    }

    /**
     * Prepares a statement for execution and returns a Microestc\OscarDB\ACI_PDO\ACIStatement object.
     *
     * @param  string $statement Valid SQL statement for the target database server.
     * @param  array $driver_options Attribute values for the ACIStatement object
     *
     * @return mixed Returns a ACIStatement on success, false otherwise
     */
    public function prepare($statement, $driver_options = [])
    {
        $tokens = explode('?', $statement);

        $count = count($tokens) - 1;
        if ($count) {
            $statement = '';
            for ($i = 0; $i < $count; $i++) {
                $statement .= trim($tokens[$i])." :{$i} ";
            }
            $statement .= trim($tokens[$i]);
        }

        $this->queryString = $statement;
        $stmt = $this->conn->prepare($this->queryString);
        $this->stmt = new ACIStatement($stmt, $this, $this->queryString, $driver_options);

        return $this->stmt;
    }

    /**
     * Executes an SQL statement, returning a result set as a Microestc\OscarDB\ACI_PDO\ACIStatement object
     * on success or false on failure.
     *
     * @param  string $statement Valid SQL statement for the target database server.
     * @param  int $mode The fetch mode must be one of the PDO::FETCH_* constants.
     * @param  mixed $type Column number, class name or object depending on PDO::FETCH_* constant used
     * @param  array $ctorargs Constructor arguments
     *
     * @return mixed Returns a ACIStatement on success, false otherwise
     */
    public function query($statement, $mode = null, $type = null, $ctorargs = [])
    {
        return $this->conn->query($statement, $mode, $type, $ctorargs);
    }

    /**
     * Quotes a string for use in a query.
     *
     * @param  string $string The string to be quoted.
     * @param  int $parameter_type Provides a data type hint for drivers that have alternate quoting styles.
     *
     * @return string Returns false
     */
    public function quote($string, $parameter_type = \PDO::PARAM_STR)
    {
        return $this->conn->quote($string, $parameter_type);
    }

    /**
     * Rolls back a transaction.
     *
     * @throws ACIException If aci_rollback returns an error.
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function rollBack()
    {
        if ($this->inTransaction()) {
            $r = $this->conn.rollBack();
            if (! $r) {
                throw new ACIException($this->setErrorInfo('40003'));
            }
            $this->transaction = ! $this->flipExecuteMode();

            return true;
        }

        return false;
    }

    /**
     * Set an attribute.
     *
     * @param int $attribute PDO::ATTR_* attribute identifier
     * @param mixed $value Value of PDO::ATTR_* attribute
     *
     * @return true
     */
    public function setAttribute($attribute, $value)
    {
        $this->attributes[$attribute] = $value;

        return true;
    }

    /**
     * CUSTOM CODE FROM HERE DOWN.
     *
     * All code above this is overriding the PDO base code
     * All code below this are custom helpers or other functionality provided by the aci_* functions
     */

    /**
     * Flip the execute mode.
     *
     * @return int Returns true
     */
    public function flipExecuteMode()
    {
        $this->setExecuteMode($this->getExecuteMode() == \ACI_COMMIT_ON_SUCCESS ? \ACI_NO_AUTO_COMMIT : \ACI_COMMIT_ON_SUCCESS);

        return true;
    }

    /**
     * Get the current Execute Mode for the conneciton.
     *
     * @return int Either \ACI_COMMIT_ON_SUCCESS or \ACI_NO_AUTO_COMMIT
     */
    public function getExecuteMode()
    {
        return $this->mode;
    }

    /**
     * Returns the oscar connection handle for use with other aci_ functions.
     *
     * @return bool|aci|resource|string The oscar connection handle
     */
    public function getACIResource()
    {
        return $this->conn;
    }

    /**
     * Set the PDO errorInfo array values.
     *
     * @param string $code SQLSTATE identifier
     * @param string $error Driver error code
     * @param string $message Driver error message
     *
     * @return array Returns the PDO errorInfo array
     */
    private function setErrorInfo($code = null, $error = null, $message = null)
    {
        if (is_null($code)) {
            $code = 'JF000';
        }

        if (is_null($error)) {
            $e = $this->conn->errorInfo();
            $error = $e['code'];
            $message = $e['message'].(empty($e['sqltext']) ? '' : ' - SQL: '.$e['sqltext']);
        }

        $this->error[0] = $code;
        $this->error[1] = $error;
        $this->error[2] = $message;

        return $this->error;
    }

    /**
     * Set the execute mode for the connection.
     *
     * @param int $mode Either \ACI_COMMIT_ON_SUCCESS or \ACI_NO_AUTO_COMMIT
     *
     * @return bool
     *
     * @throws ACIException If any value other than the above are passed in
     */
    public function setExecuteMode($mode)
    {
        if ($mode === \ACI_COMMIT_ON_SUCCESS || $mode === \ACI_NO_AUTO_COMMIT) {
            $this->mode = $mode;

            return true;
        }

        throw new ACIException($this->setErrorInfo('0A000', '9999', "Invalid commit mode specified: {$mode}"));
    }
}
