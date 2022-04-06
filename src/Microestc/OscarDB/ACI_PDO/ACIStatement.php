<?php

namespace Microestc\OscarDB\ACI_PDO;

use \Microestc\OscarDB\ACI_PDO\ACI as ACI;

class ACIStatement extends \PDOStatement
{
    /**
     * @var aci statement Statement handle
     */
    protected $stmt;

    /**
     * @var aci
     */
    protected $aci;

    /**
     * @var array Database statement attributes
     */
    protected $attributes;

    /**
     * @var string SQL statement
     */
    protected $sql = '';

    /**
     * @var array PDO => ACI data types conversion var
     */
    protected $datatypes = [
        \PDO::PARAM_BOOL => 3,
        // there is no SQLT_NULL, but Oscar will insert a null value if it receives an empty string
        \PDO::PARAM_NULL => 1,
        \PDO::PARAM_INT => 3,
        \PDO::PARAM_STR => 1,
        \PDO::PARAM_INPUT_OUTPUT => 1,
        \PDO::PARAM_LOB => 113,
    ];

    /**
     * Constructor.
     *
     * @param resource $stmt Statement handle created with aci_parse()
     * @param ACI $aci The ACI object for this statement
     * @param array $options Options for the statement handle
     *
     * @throws ACIException if $stmt is not a vaild aci statement resource
     */
    public function __construct($stmt, ACI $aci, $sql = '', $options = [])
    {
        $resource_type = strtolower(get_resource_type($stmt));

        if ($resource_type != 'acistatement') {
            throw new ACIException($this->setErrorInfo('0A000', '9999', "Invalid resource received: {$resource_type}"));
        }

        $this->stmt = $stmt;
        $this->aci = $aci;
        $this->sql = $sql;
        $this->attributes = $options;
    }

    /**
     * Destructor - Checks for an aci statment resource and frees the resource if needed.
     */
    public function __destruct()
    {
        if (strtolower(get_resource_type($this->stmt)) == 'acistatement') {
            // $this->stmt->close();
        }

        //Also test for descriptors
    }

    /**
     * Bind a column to a PHP variable.
     *
     * @param  mixed $column Number of the column (1-indexed) in the result set
     * @param  mixed $param Name of the PHP variable to which the column will be bound.
     * @param  int $type Data type of the parameter, specified by the PDO::PARAM_* constants.
     * @param  int $maxlen A hint for pre-allocation.
     * @param  mixed $driverdata Optional parameter(s) for the driver.
     *
     * @throws \InvalidArgumentException If an unknown data type is passed in
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function bindColumn($column, &$param, $data_type = null, $maxlen = null, $driverdata = null)
    {
        if (! is_numeric($column) || $column < 1) {
            throw new \InvalidArgumentException("Invalid column specified: {$column}");
        }

        if (! isset($this->datatypes[$data_type])) {
            throw new \InvalidArgumentException("Unknown data type in aci_bind_by_name: {$data_type}");
        }
        return $this->stmt->bindColumn($column, $param, $data_type, $maxlen, $driverdata);
    }

    /**
     * Binds a parameter to the specified variable name.
     *
     * @param  mixed $parameter Parameter identifier
     * @param  mixed $variable Name of the PHP variable to bind to the SQL statement parameter
     * @param  int $data_type Explicit data type for the parameter using the PDO::PARAM_* constants
     * @param  int $length Length of the data type
     * @param  mixed $driver_options
     *
     * @throws \InvalidArgumentException If an unknown data type is passed in
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function bindParam($parameter, &$variable, $data_type = \PDO::PARAM_STR, $length = -1, $driver_options = null)
    {
        if (is_numeric($parameter)) {
            $parameter = ":{$parameter}";
        }

        if (! isset($this->datatypes[$data_type])) {
            if ($data_type === (\PDO::PARAM_INT | \PDO::PARAM_INPUT_OUTPUT)) {
                $data_type = \PDO::PARAM_STR;
                // $length = $length > 8000 ? $length : 8000;
            } else {
                throw new \InvalidArgumentException("Unknown data type in aci_bind_by_name: {$data_type}");
            }
        }

        //Bind the parameter
        $result = $this->stmt->bindParam($parameter, $variable, $this->datatypes[$data_type], $length, $driver_options);

        return $result;
    }

    /**
     * Binds a value to a parameter.
     *
     * @param  mixed $parameter Parameter identifier.
     * @param  mixed $value The value to bind to the parameter
     * @param  int $data_type Explicit data type for the parameter using the PDO::PARAM_* constants
     *
     * @throws \InvalidArgumentException If an unknown data type is passed in
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function bindValue($parameter, $value, $data_type = \PDO::PARAM_STR)
    {
        if (is_numeric($parameter)) {
            $parameter = ":{$parameter}";
        }

        if (! isset($this->datatypes[$data_type])) {
            throw new \InvalidArgumentException("Unknown data type in aci_bind_by_name: {$data_type}");
        }

        //Bind the parameter
        $result = $this->stmt->bindValue($parameter, $value, $this->datatypes[$data_type]);

        return $result;
    }

    /**
     * Closes the cursor, enabling the statement to be executed again.
     * 
     * Todo implement this method instead of always returning true
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function closeCursor()
    {
        return $this->stmt->closeCursor();
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return int Returns the number of columns in the result set represented by the PDOStatement object.
     *             If there is no result set, returns 0.
     */
    public function columnCount()
    {
        return $this->stmt->columnCount();
    }

    /**
     * Dump an SQL prepared command.
     *
     * @return string print_r of the sql and parameters array
     */
    public function debugDumpParams()
    {
        return $this->stmt->debugDumpParams();
    }

    /**
     * Fetch the SQLSTATE associated with the last operation on the statement handle.
     *
     * @return mixed Returns an SQLSTATE or NULL if no operation has been run
     */
    public function errorCode()
    {
        return $this->stmt->errorCode();
    }

    /**
     * Fetch extended error information associated with the last operation on the statement handle.
     *
     * @return array array of error information about the last operation performed
     */
    public function errorInfo()
    {
        return $this->stmt->errorInfo();
    }

    /**
     * Executes a prepared statement.
     *
     * @param  array $input_parameters An array of values with as many elements as there are bound parameters in the
     *                                 SQL statement being executed
     *
     * @return bool Returns TRUE on success or FALSE on failure.
     */
    public function execute($input_parameters = null)
    {
        $result = $this->stmt->execute($input_parameters);
        return $result;
    }

    /**
     * Fetches the next row from a result set.
     *
     * @param  int $fetch_style Controls how the next row will be returned to the caller. This value must be one of
     *                          the PDO::FETCH_* constants
     * @param  int $cursor_orientation For a Statement object representing a scrollable cursor, this value determines
     *                                 which row will be returned to the caller.
     * @param  int $cursor_offset Specifies the absolute number of the row in the result set that shall be fetched
     *
     * @return mixed The return value of this function on success depends on the fetch type.
     *               In all cases, FALSE is returned on failure.
     */
    public function fetch($fetch_style = \PDO::FETCH_CLASS, $cursor_orientation = \PDO::FETCH_ORI_NEXT, $cursor_offset = 0)
    {
        return $this->stmt->fetch($fetch_style, $cursor_orientation, $cursor_offset);
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param int|null $fetch_style    Controls how the next row will be returned to the caller. This value must be one
     *                                 of the PDO::FETCH_* constants
     * @param mixed    $fetch_argument This argument have a different meaning depending on the value of the
     *                                 fetch_style parameter
     * @param array    $ctor_args      Arguments of custom class constructor when the fetch_style parameter is
     *                                 PDO::FETCH_CLASS.
     *
     * @return array
     */
    public function fetchAll($fetch_style = \PDO::FETCH_CLASS, $fetch_argument = null, $ctor_args = [])
    {
        if ($fetch_style != \PDO::FETCH_CLASS && $fetch_style != \PDO::FETCH_ASSOC) {
            throw new \InvalidArgumentException(
                "Invalid fetch style requested: {$fetch_style}. Only PDO::FETCH_CLASS and PDO::FETCH_ASSOC suported."
            );
        }

        $rs = $this->stmt->fetchAll($fetch_style, $fetch_argument, $ctor_args);
        return $rs;
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param  int $column_number 0-indexed number of the column you wish to retrieve from the row.
     *                            If no value is supplied, fetchColumn fetches the first column.
     *
     * @return mixed single column in the next row of a result set
     */
    public function fetchColumn($column_number = 0)
    {
        if (! is_int($column_number)) {
            throw new ACIException($this->setErrorInfo(
                '0A000',
                '9999',
                "Invalid Column type specfied: {$column_number}. Expecting an int."
            ));
        }

        $rs = $this->stmt->fetchColumn($column_number);
        return $rs;
    }

    /**
     * Fetches the next row and returns it as an object.
     *
     * @param string $class_name Name of the created class
     * @param array $ctor_args Elements of this array are passed to the constructor
     *
     * @return bool Returns an instance of the required class with property names that correspond to the column names
     *              or FALSE on failure.
     */
    public function fetchObject($class_name = 'stdClass', $ctor_args = null)
    {
        return $this->fetchObject($class_name, $ctor_args);
    }

    /**
     * Retrieve a statement attribute.
     *
     * @param int $attribute The attribute number
     * @return mixed Returns the value of the attribute on success or null on failure
     */
    public function getAttribute($attribute)
    {
        return $this->stmt->getAttribute($attribute);
    }

    /**
     * Returns metadata for a column in a result set.
     *
     * @param int #column The 0-indexed column in the result set.
     * @return array Returns an associative array representing the metadata for a single column
     */
    public function getColumnMeta($column)
    {
        if (! is_int($column)) {
            throw new ACIException($this->setErrorInfo(
                '0A000',
                '9999',
                "Invalid Column type specfied: {$column}. Expecting an int."
            ));
        }

        $column++;

        return [
            'native_type' => aci_field_type($this->stmt, $column),
            'driver:decl_type' => aci_field_type_raw($this->stmt, $column),
            'name' => aci_field_name($this->stmt, $column),
            'len' => aci_field_size($this->stmt, $column),
            'precision' => aci_field_precision($this->stmt, $column),
        ];
    }

    /**
     * Advances to the next rowset in a multi-rowset statement handle.
     *
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function nextRowset()
    {
        return true;
    }

    /**
     * Returns the number of rows affected by the last SQL statement.
     *
     * @return int Returns the number of rows affected as an integer, or FALSE on errors.
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Set a statement attribute.
     *
     * @param int $attribute The attribute number
     * @param mixed $value Value of named attribute
     * @return bool Returns TRUE
     */
    public function setAttribute($attribute, $value)
    {
        return $this->stmt->setAttribute($attribute, $value);
    }

    /**
     * Set the default fetch mode for this statement.
     *
     * @param int $mode The fetch mode must be one of the PDO::FETCH_* constants.
     * @param mixed $type Column number, class name or object depending on PDO::FETCH_* constant used
     * @param array $ctorargs Constructor arguments
     * @return bool Returns TRUE on success or FALSE on failure
     */
    public function setFetchMode($mode, $type = null, $ctorargs = [])
    {
        return $this->stmt->setFetchMode($mode, $type, $ctorargs);
    }


    /**
     * Returns the aci statement handle for use with other aci_ functions.
     *
     * @return aci statment The aci statment handle
     */
    public function getACIResource()
    {
        return $this->stmt;
    }
}
