<?php


class ActionsCardPatient extends CommonObject
{
    var $db;                            //!< To store db handler
    var $error;                         //!< To return error code (or message)
    var $errors=array();                //!< To return several error codes (or messages)

    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function ActionsCardPatient($DB,$targetmodule,$canvas,$card)
    {
        $this->db = $DB;

        //print 'tttttttt'; exit;

        return 1;
    }



    function xdoActions()
    {


    }


}