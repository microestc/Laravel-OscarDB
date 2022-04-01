<?php

namespace {
    $ACITransactionStatus = true;
    $ACIStatementStatus = true;
    $ACIConnectionStatus = true;
    $ACIExecuteStatus = true;
    $ACIFetchStatus = true;
    $ACIBindChangeStatus = false;
    $ACIBindByNameTypeReceived = null;
}

namespace OscarDB\ACI_PDO {
    // ACI specific
    if (! function_exists("OscarDB\ACI_PDO\aci_error")) {
        function aci_error($a = '')
        {
            global $ACIExecuteStatus, $ACIFetchStatus, $ACITransactionStatus;

            return ($ACIExecuteStatus && $ACIFetchStatus && $ACITransactionStatus)
                ? false
                : ['code' => 0,'message' => '', 'sqltext' => ''];
        }
    }

    if (! function_exists("OscarDB\ACI_PDO\aci_connect")) {
        function aci_connect($a = '')
        {
            global $ACITransactionStatus;

            return $ACITransactionStatus ? 'aci' : false;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_pconnect")) {
        function aci_pconnect($a = '')
        {
            global $ACITransactionStatus;

            return $ACITransactionStatus ? 'aci' : false;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_close")) {
        function aci_close($a = '')
        {
            global $ACITransactionStatus;
            $ACITransactionStatus = false;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_commit")) {
        function aci_commit($a = '')
        {
            global $ACITransactionStatus;

            return $ACITransactionStatus;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_rollback")) {
        function aci_rollback($a = '')
        {
            global $ACITransactionStatus;

            return $ACITransactionStatus;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_parse")) {
        function aci_parse($a = '', $b = '')
        {
            global $ACITransactionStatus;

            return $ACITransactionStatus ? 'aci statement' : false;
        }
    }

    // ACI Statement specific
    if (! function_exists("OscarDB\ACI_PDO\get_resource_type")) {
        function get_resource_type($a = '')
        {
            global $ACIStatementStatus;

            return $ACIStatementStatus ? $a : 'invalid';
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_bind_by_name")) {
        function aci_bind_by_name($a = '', $b = '', &$c, $d = '', $e = '')
        {
            global $ACIStatementStatus, $ACIBindChangeStatus, $ACIBindByNameTypeReceived;

            $ACIBindByNameTypeReceived = $e;

            if ($ACIBindChangeStatus) {
                $c = 'aci_bind_by_name';
            }

            return $ACIStatementStatus;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_num_fields")) {
        function aci_num_fields($a = '')
        {
            return 1;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_free_statement")) {
        function aci_free_statement($a = '')
        {
            global $ACIStatementStatus;
            $ACIStatementStatus = false;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_execute")) {
        function aci_execute($a = '', $b = '')
        {
            global $ACIExecuteStatus;

            return $ACIExecuteStatus;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_fetch_assoc")) {
        function aci_fetch_assoc($a = '')
        {
            global $ACIFetchStatus;

            return $ACIFetchStatus ? ['FNAME' => 'Test', 'LNAME' => 'Testerson', 'EMAIL' => 'tester@testing.com'] : false;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_fetch_row")) {
        function aci_fetch_row($a = '')
        {
            global $ACIFetchStatus;

            return $ACIFetchStatus ? [0 => 'Test', 1 => 'Testerson', 2 => 'tester@testing.com'] : false;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_fetch_array")) {
        function aci_fetch_array($a = '')
        {
            global $ACIFetchStatus;

            return $ACIFetchStatus ? [0 => 'Test', 1 => 'Testerson', 2 => 'tester@testing.com', 'FNAME' => 'Test', 'LNAME' => 'Testerson', 'EMAIL' => 'tester@testing.com'] : false;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_fetch_all")) {
        function aci_fetch_all($a = '', &$b)
        {
            global $ACIFetchStatus;
            $b = [['FNAME' => 'Test', 'LNAME' => 'Testerson', 'EMAIL' => 'tester@testing.com']];

            return $ACIFetchStatus;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_field_type")) {
        function aci_field_type($a, $b)
        {
            return 1;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_field_type_raw")) {
        function aci_field_type_raw($a, $b)
        {
            return 1;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_field_name")) {
        function aci_field_name($a, $b)
        {
            return 1;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_field_size")) {
        function aci_field_size($a, $b)
        {
            return 1;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_field_precision")) {
        function aci_field_precision($a, $b)
        {
            return 1;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_num_rows")) {
        function aci_num_rows($a)
        {
            return 1;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_client_version")) {
        function aci_client_version()
        {
            return 'Test Return';
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_free_descriptor")) {
        function aci_free_descriptor($a)
        {
            return $a;
        }
    }
    if (! function_exists("OscarDB\ACI_PDO\aci_internal_debug")) {
        function aci_internal_debug($a)
        {
            global $ACITransactionStatus;
            $ACITransactionStatus = $a;
        }
    }
}
