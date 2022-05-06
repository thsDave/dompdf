<?php

require_once 'Connection.php';

class Model extends Connection
{
	public function pst($query, $arr_data = [], $expect_values = true)
    {
        $pdo = parent::connect();
        $pst = $pdo->prepare($query);
        if ($pst->execute($arr_data)) {
            if ($expect_values)
                $res = $pst->fetchAll();
            else
                $res = true;
        }else {
            $res = false;
        }
        return $res;
    }

    public function info_login($email, $pass = null)
    {
        $arr_data = ['email' => $email];

        $res = $this->pst("SELECT * FROM tbl_users WHERE email = :email AND idstatus = 1", $arr_data);

        if (!empty($res))
        {
            if (!is_null($pass))
                $iduser = (password_verify($pass, $res[0]->pass)) ? $res[0]->iduser : false;
            else
                $iduser = $res[0]->iduser;

            if ($iduser)
            {
                $res = $this->pst("CALL sp_getlvl(:iduser)", ['iduser' => $iduser]);

                if (!empty($res))
                {
                    $level = $res[0]->level;

                    $res = $this->pst("SELECT COUNT(*) AS 'total' FROM tbl_inputs WHERE iduser = :iduser", ['iduser' => $iduser]);

                    if (!empty($res))
                    {
                        if ($res[0]->total > 0)
                        {
                            # Obtenemos los datos del usuario

                            $_SESSION['session_fleet'] = $this->user_info($iduser);

                            $_SESSION['lang'] = [ 'lanicon' => $_SESSION['session_fleet']['lanicon'], 'lancode' => $_SESSION['session_fleet']['lancode'] ];

                            /**** AGREGANDO DATOS PARA DASHBOARD ****/

                            # Guardamos el último ingreso

                            $llogin = $this->pst("SELECT DATE_FORMAT(indate, '🗓️ %d/%m/%Y ⌚ %r') as indate FROM tbl_inputs WHERE iduser = :iduser ORDER BY idinput DESC LIMIT 1", ['iduser' => $iduser]);

                            $_SESSION['session_fleet']['llogin'] = $llogin[0]->indate;

                            # Guardamos el total de bitácoras

                            $tbin = $this->pst("SELECT COUNT(*) AS 'total' FROM tbl_binnacles WHERE inituser = :iduser", ['iduser' => $iduser]);

                            $_SESSION['session_fleet']['tbin'] = (!empty($tbin)) ? $tbin[0]->total : false;

                            # Guardamos el último vehiculo y destino registrado

                            $lbit = $this->pst("SELECT b.destination, a.plate FROM tbl_binnacles b INNER JOIN tbl_autos a ON b.idauto = a.idauto WHERE b.inituser = :iduser ORDER BY b.idbinnacle DESC LIMIT 1", ['iduser' => $iduser]);

                            $_SESSION['session_fleet']['ldestination'] = (!empty($lbin)) ? $lbit[0]->destination : false;

                            $_SESSION['session_fleet']['lcar'] = (!empty($lbin)) ? $lbit[0]->plate : false;

                            # Guardamos el ingreso actual

                            $this->pst("INSERT INTO tbl_inputs(iduser) VALUES (:iduser)", ['iduser' => $iduser], false);

                            return true;
                        }
                        else
                        {
                            return 'firstIn';
                        }
                    }
                    else
                    {
                        return false;
                    }
                }
                else
                {
                    return false;
                }
            }
            else
            {
                return false;
            }
        }
        else
        {
            return false;
        }
    }

    public function outputs($id)
    {
        $this->pst("INSERT INTO tbl_outputs(iduser) VALUES (:id)", ['id' => $id], false);
    }

	public function user_info($iduser)
    {
    	$res = $this->pst("CALL sp_userinfo(:iduser)", ['iduser' => $iduser]);

    	if (!empty($res))
		{
			$info = [];

			foreach($res as $val)
			{
                $info['id'] = $val->iduser;
				$info['name'] = $val->name;
				$info['email'] = $val->email;
				$info['level'] = $val->level;
                $info['region'] = $val->region;
                $info['idlang'] = $val->idlang;
                $info['lancode'] = $val->lancode;
                $info['lanicon'] = $val->lanicon;
				$info['position'] = $val->position;
                $info['pic'] = base64_encode($val->picture);
                $info['status'] = $val->status;
                $info['idstatus'] = $val->idstatus;
                $info['approval'] = $val->approval;
                $info['idcountry'] = $val->idcountry;
			}

			return $info;
		}
		else
		{
			return false;
		}
    }

    public function lang_list()
    {
        $res = $this->pst("SELECT * FROM tbl_languages");

        if (!empty($res))
        {
            $info = [];

            foreach($res as $val)
            {
                $info['idlang'][] = $val->idlang;
                $info['language'][] = $val->language;
                $info['lancode'][] = $val->lancode;
                $info['lanicon'][] = $val->lanicon;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function region_list($idcountry = null)
    {
        $id = (is_null($idcountry)) ? 1 : $idcountry;

        $res = $this->pst("SELECT * FROM tbl_regions WHERE idcountry = :id", ['id' => $id]);

        if (!empty($res))
        {
            $info = [];

            foreach($res as $val)
            {
                $info['idregion'][] = $val->idregion;
                $info['region'][] = $val->region;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function providers_list($country)
    {
        $res = $this->pst("CALL sp_providerslist(:country)", ['country' => $country]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idprovider'][] = $val->idprovider;
                $info['name'][] = $val->name;
                $info['contact'][] = $val->contact;
                $info['tel'][] = $val->tel;
                $info['idtype'][] = $val->idtype;
                $info['typeprovider'][] = $val->typeprovider;
                $info['country'][] = $val->country;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function type_providers_list()
    {
        $res = $this->pst("SELECT * FROM tbl_typeofproviders");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idtype'][] = $val->idtype;
                $info['typeprovider'][] = $val->typeprovider;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function info_provider($id)
    {
        $res = $this->pst("CALL sp_infoprovider(:id)", ['id' => $id]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idprovider'] = $val->idprovider;
                $info['name'] = $val->name;
                $info['contact'] = $val->contact;
                $info['tel'] = $val->tel;
                $info['idtype'] = $val->idtype;
                $info['typeprovider'] = $val->typeprovider;
                $info['country'] = $val->country;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function add_provider($name, $type, $contact, $tel)
    {
        $arr_data = [
            'name' => $name,
            'type' => $type,
            'contact' => $contact,
            'tel' => $tel,
            'country' => $_SESSION['session_fleet']['idcountry']
        ];

        $res = $this->pst("INSERT INTO tbl_providers VALUES (NULL, :name, :contact, :tel, :type, :country, 1)", $arr_data, false);

        return $res;
    }

    public function edit_provider($name, $type, $contact, $tel, $id)
    {
        $arr_data = [
            'name' => $name,
            'type' => $type,
            'contact' => $contact,
            'tel' => $tel,
            'id' => $id
        ];

        $query = "UPDATE tbl_providers SET name = :name, contact = :contact, tel = :tel, idtype = :type WHERE idprovider = :id";

        $res = $this->pst($query, $arr_data, false);

        return $res;
    }

    public function remove_provider($id)
    {
        $res = $this->pst("UPDATE tbl_providers SET idstatus = 2 WHERE idprovider = :id", ['id' => $id], false);

        return $res;
    }

    public function autos_list($country, $type)
    {
        $res = $this->pst("CALL sp_autoslist(:country, :type)", ['country' => $country, 'type' => $type]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idauto'][] = $val->idauto;
                $info['plate'][] = $val->plate;
                $info['color'][] = $val->color;
                $info['brand'][] = $val->brand;
                $info['model'][] = $val->model;
                $info['annum'][] = $val->annum;
                $info['motor'][] = $val->motor;
                $info['chassis'][] = $val->chassis;
                $info['vin'][] = $val->vin;
                $info['name'][] = $val->name;
                $info['region'][] = $val->region;
                $info['nextmant'][] = $val->nextmant;
                $info['mileage'][] = $val->mileage;
                $info['type'][] = $val->type;
                $info['statusbin'][] = $val->statusbin;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function info_auto($idauto)
    {
        $res = $this->pst("CALL sp_infoauto(:id)", ['id' => $idauto]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idauto'] = $val->idauto;
                $info['plate'] = $val->plate;
                $info['annum'] = $val->annum;
                $info['provider'] = $val->provider;
                $info['brand'] = $val->brand;
                $info['model'] = $val->model;
                $info['color'] = $val->color;
                $info['motor'] = $val->motor;
                $info['chassis'] = $val->chassis;
                $info['vin'] = $val->vin;
                $info['region'] = $val->region;
                $info['mileage'] = $val->mileage;
                $info['nextmant'] = $val->nextmant;
                $info['type'] = $val->type;
                $info['statusbin'] = $val->statusbin;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function autos_picture($idauto, $idbin = 0)
    {
        $res = $this->pst("CALL sp_autospics(:idauto, :idbin)", ['idauto' => $idauto, 'idbin' => $idbin]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idsheet'][] = $val->idsheet;
                $info['side'][] = $val->side;
                $info['pic'][] = $val->pic;
                $info['idtype'][] = $val->idtype;
                $info['obs'][] = $val->obs;
                $info['type'][] = $val->type;
                $info['description'][] = $val->description;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function brands_list()
    {
        $res = $this->pst("SELECT * FROM tbl_brands");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idbrand'][] = $val->idbrand;
                $info['brand'][] = $val->brand;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function autoitems_list()
    {
        $res = $this->pst("SELECT * FROM tbl_autoitems WHERE idstatus = 1");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->iditem;
                $info['item'][] = $val->item;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function autotype_list()
    {
        $res = $this->pst("SELECT * FROM tbl_autotypes");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idtype'][] = $val->idtype;
                $info['type'][] = $val->type;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function add_auto($info_auto)
    {
        $query = "CALL sp_addauto(";

        foreach ($info_auto as $key => $value)
            $query .= ($key == 'tipo') ? ":{$key})" : ":{$key}, ";

        $res = $this->pst($query, $info_auto, false);

        return $res;
    }

    public function edit_auto($info_auto)
    {
        $query = "CALL sp_editauto(";

        foreach ($info_auto as $key => $value)
            $query .= ($key == 'idauto') ? ":{$key})" : ":{$key}, ";

        $res = $this->pst($query, $info_auto, false);

        return $res;
    }

    public function remove_auto($id)
    {
        $res = $this->pst("UPDATE tbl_autos SET idstatus = 2 WHERE idauto = :id", ['id' => $id], false);

        return $res;
    }

    public function info_binnacle($idauto, $idbin = 0)
    {
        $res = $this->pst("CALL sp_infobinnacle(:idauto, :idbin)", ['idauto' => $idauto, 'idbin' => $idbin]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['plate'] = $val->plate;
                $info['idbinnacle'] = $val->idbinnacle;
                $info['idauto'] = $val->idauto;
                $info['initkm'] = $val->initkm;
                $info['endkm'] = $val->endkm;
                $info['outdate'] = $val->outdate;
                $info['entrydate'] = $val->entrydate;
                $info['outtime'] = $val->outtime;
                $info['entrytime'] = $val->entrytime;
                $info['inituser'] = $val->inituser;
                $info['initusername'] = $val->initusername;
                $info['enduser'] = $val->enduser;
                $info['endusername'] = $val->endusername;
                $info['destination'] = $val->destination;
                $info['reason'] = $val->reason;
                $info['observation'] = (is_null($val->observation)) ? 'Sin comentarios.' : $val->observation;
                $info['idstatus'] = $val->idstatus;
                $info['idsheet'] = $val->idsheet;
                $info['levelgas'] = $val->levelgas;
                $info['initobservation'] = (empty($val->initobservation)) ? 'Sin comentarios.' : $val->initobservation;
                $info['endobservation'] = (empty($val->endobservation)) ? 'Sin comentarios.' : $val->endobservation;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function add_blowdetail($arr_data)
    {
        return $this->pst("INSERT INTO tbl_blowsdetail VALUES (:id, :side, :pic, :type, :obs)", $arr_data, false);
    }

    public function delete_picture($idsheet, $side)
    {
        $res = $this->pst("DELETE FROM tbl_blowsdetail WHERE idsheet = :id AND side = :side", ['id' => $idsheet, 'side' => $side], false);

        return $res;
    }

    public function bin_check($iduser)
    {
        $res = $this->pst("SELECT * FROM tbl_binnacles WHERE inituser = :id AND endkm IS NULL", ['id' => $iduser]);

        $info = [];

        if (!empty($res))
        {
            foreach ($res as $val)
            {
                $info['idauto'] = $val->idauto;
                $info['idbin'] = $val->idbinnacle;
            }

            $info['res'] = 'denied';
        }
        else
        {
            $info['res'] = 'authorized';
        }

        return $info;
    }

    public function open_binnacle($data)
    {
        return $this->pst("CALL sp_openbinnacle(:idauto, :initkm, :outdate, :outtime, :iduser, :destination, :reason, :observation)", $data, false);
    }

    public function close_binnacle($arr_data)
    {
        return $this->pst("CALL sp_closebinnacle(:entryd, :entryt, :kmin, :lvlgas, :obs, :idbin, :idauto, :iduser)", $arr_data, false);
    }

    public function last_detail_sheet($idauto)
    {
        $res = $this->pst("CALL sp_currentoutputsheet(:id)", ['id' => $idauto]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['iditem'][] = $val->iditem;
                $info['item'][] = $val->item;
                $info['idstatus'][] = $val->idstatus;
                $info['status'][] = $val->status;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function last_sheet($id)
    {
        $res = $this->pst("CALL sp_lastsheet(:id)", ['id' => $id]);

        if (!empty($res))
        {
            foreach($res as $val)
            {
                $sheet['idsheet'] = $val->idsheet;
                $sheet['idbinnacle'] = $val->idbinnacle;
                $sheet['levelgas'] = $val->levelgas;
                $sheet['initobservation'] = $val->initobservation;
                $sheet['endobservation'] = $val->endobservation;
            }

            return $sheet;
        }
        else
        {
            return false;
        }
    }

    public function current_photos($idauto)
    {
        $res = $this->pst("CALL sp_currentphotos(:idauto)", ['idauto' => $idauto]);

        if (!empty($res))
        {
            foreach($res as $val)
            {
                $info['idsheet'][] = $val->idsheet;
                $info['side'][] = $val->side;
                $info['pic'][] = $val->pic;
                $info['idtype'][] = $val->idtype;
                $info['type'][] = $val->type;
                $info['description'][] = $val->description;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function licenses_list($iduser = null)
    {
        if (is_null($iduser))
            $res = $this->pst("CALL sp_licenseslist(0)");
        else
            $res = $this->pst("CALL sp_licenseslist(:id)", ['id' => $iduser]);

        if (!empty($res))
        {
            $info = [];

            foreach($res as $val)
            {
                $info['idlicense'][] = $val->idlicense;
                $info['iduser'][] = $val->iduser;
                $info['ndocument'][] = $val->ndocument;
                $info['duedate'][] = $val->duedate;
                $info['idtype'][] = $val->idtype;
                $info['type'][] = $val->type;
                $info['idstatus'][] = $val->idstatus;
                $info['status'][] = $val->status;
                $info['approval'][] = $val->approval;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function add_license($ndoc, $duedate, $type, $iduser)
    {
        $arr_data = [
            'ndoc' => $ndoc,
            'due' => $duedate,
            'type' => $type,
            'iduser' => $iduser
        ];

        $res = $this->pst("CALL sp_addlicense(:ndoc, :due, :type, :iduser)", $arr_data);

        if (!empty($res))
        {
            foreach ($res as $val)
                $info = $val->res;

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function info_license($id)
    {
        $res = $this->pst("SELECT * FROM tbl_licenses WHERE idlicense = :id", ['id' => $id]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idlic'] = $val->idlicense;
                $info['ndoc'] = $val->ndocument;
                $info['duedate'] = $val->duedate;
                $info['type'] = $val->idtype;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function edit_license($ndoc, $duedate, $type, $iduser)
    {
        $arr_data = [
            'ndoc' => $ndoc,
            'due' => $duedate,
            'type' => $type,
            'iduser' => $iduser
        ];

        return $this->pst("CALL sp_editlicense(:ndoc, :due, :type, :iduser)", $arr_data);
    }

    public function approve_license($id)
    {
        $res = $this->pst('CALL sp_drivervalidator(:id, "approval")', ['id' => $id], false);
        return ($res) ? true : false;
    }

    public function deny_license($id)
    {
        $res = $this->pst('CALL sp_drivervalidator(:id, "deny")', ['id' => $id], false);
        return ($res) ? true : false;
    }

    public function delete_license($id)
    {
        $res = $this->pst('DELETE FROM tbl_licenses WHERE idlicense = :id', ['id' => $id], false);
        return ($res) ? true : false;
    }

    public function type_licenses_list()
    {
        $res = $this->pst("SELECT * FROM tbl_typeoflicenses");

        if (!empty($res))
        {
            $info = [];

            foreach($res as $val)
            {
                $info['idtype'][] = $val->idtype;
                $info['type'][] = $val->type;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function update_user($data_user)
    {
        $id = (isset($_SESSION['val'])) ? $_SESSION['val'] : $_SESSION['session_fleet']['id'];
        $name = $data_user[0];
        $position = $data_user[1];
        $level = $data_user[2];
        $status = $data_user[3];
        $lang = $data_user[4];
        $region = $data_user[5];

        $data = [
            'name' => $name,
            'position' => $position,
            'lang' => $lang,
            'region' => $region,
            'level' => $level,
            'status' => $status,
            'id' => $id
        ];

        $res = $this->pst("CALL sp_updtuser(:name, :position, :level, :region, :lang, :status, :id)", $data, false);

        if (!isset($_SESSION['val']))
            $_SESSION['session_fleet'] = $this->user_info($id);

        /**** AGREGANDO DATOS PARA DASHBOARD ****/

        # Guardamos el último ingreso

        $llogin = $this->pst("SELECT DATE_FORMAT(indate, '🗓️ %d/%m/%Y ⌚ %r') as indate FROM tbl_inputs WHERE iduser = :iduser ORDER BY idinput DESC LIMIT 1", ['iduser' => $id]);

        $_SESSION['session_fleet']['llogin'] = $llogin[0]->indate;

        # Guardamos el total de bitácoras

        $tbin = $this->pst("SELECT COUNT(*) AS 'total' FROM tbl_binnacles WHERE inituser = :iduser", ['iduser' => $id]);

        $_SESSION['session_fleet']['tbin'] = $tbin[0]->total;

        # Guardamos el último vehiculo y destino registrado

        $lbit = $this->pst("SELECT b.destination, a.plate FROM tbl_binnacles b INNER JOIN tbl_autos a ON b.idauto = a.idauto WHERE b.inituser = :iduser ORDER BY b.idbinnacle DESC LIMIT 1", ['iduser' => $id]);

        $_SESSION['session_fleet']['ldestination'] = $lbit[0]->destination;
        $_SESSION['session_fleet']['lcar'] = $lbit[0]->plate;

        return ($res) ? true : false;
    }

    public function update_userprofile($data_user)
    {
        $data = [
            'name' => $data_user[0],
            'position' => $data_user[1],
            'lang' => $data_user[4],
            'region' => $data_user[5],
            'level' => $data_user[2],
            'status' => $data_user[3],
            'id' => $_SESSION['val']
        ];

        $res = $this->pst("CALL sp_updtuser(:name, :position, :level, :region, :lang, :status, :id)", $data, false);

        return ($res) ? true : false;
    }

	public function set_cookie_token($email, $pass, $token)
	{
        $arr_data = [
            'email' => $email,
            'pass' => $pass,
            'token' => $token
        ];

		$res = $this->pst("INSERT INTO tbl_cookies VALUES (:email, :pass, :token)", $arr_data, false);

		if($res)
			return true;
		else
			return false;
	}

	public function get_cookie_token($token)
	{
		$res = $this->pst("SELECT email, pass FROM tbl_cookies WHERE sessiontoken = :token", ['token' => $token]);

        if (!empty($res))
        {
    		$info = [];

    		foreach($res as $val)
    		{
    		    $info['user'] = $val->email;
    		    $info['pass'] = $val->pass;
    		}

            return $info;
        }
        else
        {
            return false;
        }
	}

	public function set_reset_token($email, $token)
	{
        $now = date('Y-m-d');

        $arr_data = [
            'token' => $token,
            'now' => date('Y-m-d'),
            'email' => $email,
            'id' => 1
        ];

		$res = $this->pst("UPDATE tbl_users SET token = :token, tokendate = :now WHERE email = :email AND idstatus = :id", $arr_data, false);

        return ($res) ? true : false;
	}

	public function token_validator($token)
	{
		if (strlen($token) == 50)
		{
			$res = $this->pst("SELECT * FROM tbl_users WHERE token = :token", ['token' => $token]);

			if (!empty($res))
				return true;
			else
				return false;
		}
	}

	public function recover_password($pass, $token)
	{
        $data = $this->pst("SELECT iduser FROM tbl_users WHERE token = :token", ['token' => $token]);

        if (!empty($data))
        {
            unset($_SESSION['token']);

            $id = $data[0]->iduser;

            $this->pst("INSERT INTO tbl_inputs(iduser) VALUES (:id)", ['id' => $id], false);

            $arr_data = [
                'pass' => $pass,
                'token' => NULL,
                'td' => NULL,
                'fp' => 0,
                'idstatus' => 1,
                'iduser' => $id
            ];

            $query = "UPDATE tbl_users SET pass = :pass, token = :token, tokendate = :td, forgetpass = :fp, idstatus = :idstatus WHERE iduser = :iduser";

            $res = $this->pst($query, $arr_data, false);

    		return ($res) ? true : false;
        }
        else
        {
            return false;
        }
	}

	public function pass_validator($currentpwd, $iduser)
	{
		$res = $this->pst("SELECT * FROM tbl_users WHERE iduser = :iduser", ['iduser' => $iduser]);

        return (password_verify($currentpwd, $res[0]->pass)) ? true : false;
	}

	public function update_password($pass, $id)
	{
        $arr_data = [
            'pass' => $pass,
            'iduser' => $id
        ];

		$res = $this->pst("UPDATE tbl_users SET pass = :pass WHERE iduser = :iduser", $arr_data, false);

		return ($res) ? true : false;
	}

    public function thumbnail_profile()
    {
        $res = $this->pst("SELECT * FROM tbl_profilepics");

        if (!empty($res))
        {
            $fotos = [];

            foreach ($res as $val)
            {
                $fotos['id'][] = $val->idpic;
                $fotos['name'][] = $val->name;
                $fotos['format'][] = $val->format;
                $fotos['pic'][] = base64_encode($val->picture);
            }

            return $fotos;
        }
        else
        {
            return false;
        }
    }

    public function update_pic($idpic)
    {
        $arr_data = [
            'idpic' => $idpic,
            'iduser' => $_SESSION['session_fleet']['id']
        ];

        $res = $this->pst("UPDATE tbl_users SET idpic = :idpic WHERE iduser = :iduser", $arr_data, false);

        $_SESSION['session_fleet'] = $this->user_info($_SESSION['session_fleet']['id']);

        return ($res) ? true : false;
    }

    public function status_list()
    {
        $res = $this->pst("SELECT * FROM tbl_status");

        if (!empty($res))
        {
            $stts = [];

            foreach ($res as $val)
            {
                $stts['id'][] = $val->idstatus;
                $stts['status'][] = $val->status;
            }

            return $stts;
        }
        else
        {
            return false;
        }
    }

    public function user_list()
    {
        $res = $this->pst("CALL sp_userlist()");

        if (!empty($res))
        {
            $userdata = [];

            foreach ($res as $val)
            {
                $userdata['id'][] = $val->iduser;
                $userdata['name'][] = $val->name;
                $userdata['email'][] = $val->email;
                $userdata['position'][] = $val->position;
                $userdata['region'][] = $val->region;
                $userdata['language'][] = $val->language;
                $userdata['level'][] = $val->level;
                $userdata['registertype'][] = $val->registertype;
                $userdata['status'][] = $val->status;
                $userdata['approval'][] = $val->approval;
            }

            return $userdata;
        }
        else
        {
            return false;
        }
    }

    public function gas_list()
    {
        $res = $this->pst("CALL sp_gaslist(:year)", ['year' => date('Y')]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idgas'][] = $val->idgas;
                $info['idauto'][] = $val->idauto;
                $info['incometype'][] = $val->incometype;
                $info['correlative'][] = $val->correlative;
                $info['valor'][] = $val->valor;
                $info['incomedate'][] = $val->incomedate;
                $info['ticketdate'][] = $val->ticketdate;
                $info['registertime'][] = $val->registertime;
                $info['auto'][] = $val->auto;
                $info['type'][] = $val->type;
                $info['pilot'][] = $val->pilot;
                $info['initkm'][] = $val->initkm;
                $info['endkm'][] = $val->endkm;
                $info['provider'][] = $val->provider;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function gas_info($id)
    {
        $res = $this->pst("CALL sp_infogas(:id)", ['id' => $id]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idgas'] = $val->idgas;
                $info['idauto'] = $val->idauto;
                $info['incometype'] = $val->incometype;
                $info['correlative'] = $val->correlative;
                $info['valor'] = $val->valor;
                $info['incomedate'] = $val->incomedate;
                $info['ticketdate'] = $val->ticketdate;
                $info['registertime'] = $val->registertime;
                $info['auto'] = $val->auto;
                $info['type'] = $val->type;
                $info['pilot'] = $val->pilot;
                $info['initkm'] = $val->initkm;
                $info['endkm'] = $val->endkm;
                $info['provider'][] = $val->provider;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function search_pilot($idauto, $initkm, $endkm)
    {
        $res = $this->pst("CALL sp_searchpilot(:idauto, :initkm, :endkm)", ['idauto' => $idauto, 'initkm' => $initkm, 'endkm' => $endkm]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['iduser'] = $val->iduser;
                $info['name'] = $val->name;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function new_gas($arr_data)
    {
        $req = $this->pst("SELECT * FROM tbl_gasrecords WHERE correlative = :corr AND initkm = :initkm AND endkm = :endkm", ['corr' => $arr_data['correlative'], 'initkm' => $arr_data['initkm'], 'endkm' => $arr_data['endkm']]);

        if (!empty($req))
        {
            return false;
        }
        else
        {
            $res = $this->pst("CALL sp_newgas(:ticketdate, :incometype, :provider, :correlative, :auto, :valor, :initkm, :endkm, :idpilot)", $arr_data, false);

            return $res;
        }
    }

    public function remove_gas($id)
    {
        $res = $this->pst("DELETE FROM tbl_gasrecords WHERE idgas = :id", ['id' => $id], false);

        return $res;
    }

    public function new_order($arr_data)
    {
        $res = $this->pst("CALL sp_neworder(:idauto, :type, :km, :comment, :date, :iduser)", $arr_data, false);

        return $res;
    }

    public function add_orderdetail($idorder, $action)
    {
        $res = $this->pst("INSERT INTO tbl_orderdetail VALUES (:id, :act, 3)", ['id' => $idorder, 'act' => $action], false);

        return $res;
    }

    public function order_years()
    {
        $res = $this->pst("SELECT YEAR(o.entrydate) AS 'year' FROM `tbl_orders` o GROUP BY YEAR(o.entrydate)");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info[] = $val->year;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function order_list($year = null)
    {
        $year = (is_null($year)) ? date('Y') : $year;

        $res = $this->pst("CALL sp_orderlist(:y)", ['y' => $year]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idorder'][] = $val->idorder;
                $info['plate'][] = $val->plate;
                $info['model'][] = $val->model;
                $info['color'][] = $val->color;
                $info['type'][] = $val->type;
                $info['entrydate'][] = $val->entrydate;
                $info['outdate'][] = $val->outdate;
                $info['user'][] = $val->user;
                $info['idstatus'][] = $val->idstatus;
                $info['status'][] = $val->status;
                $info['km'][] = $val->km;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function info_order($id)
    {
        $res = $this->pst("CALL sp_infoorder(:id)", ['id' => $id]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idorder'] = $val->idorder;
                $info['plate'] = $val->plate;
                $info['model'] = $val->model;
                $info['color'] = $val->color;
                $info['type'] = $val->type;
                $info['entrydate'] = $val->entrydate;
                $info['outdate'] = $val->outdate;
                $info['user'] = $val->user;
                $info['idstatus'] = $val->idstatus;
                $info['status'] = $val->status;
                $info['km'] = $val->km;
                $info['comments'] = $val->comments;
                $info['idauto'] = $val->idauto;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function order_detail($id)
    {
        $res = $this->pst("SELECT * FROM tbl_orderdetail WHERE idorder = :id", ['id' => $id]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idorder'][] = $val->idorder;
                $info['action'][] = $val->action;
                $info['idstatus'][] = $val->idstatus;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function get_mtto_comment($id)
    {
        $res = $this->pst("SELECT comment FROM tbl_mttos WHERE idorder = :id", ['id' => $id]);

        return (!empty($res)) ? $res[0]->comment : false;
    }

    public function invoice_detail($id)
    {
        $res = $this->pst("CALL sp_invoicedetail(:id)", ['id' => $id]);

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idmtto'][] = $val->idmtto;
                $info['product'][] = $val->product;
                $info['quantity'][] = $val->quantity;
                $info['invoice'][] = $val->invoice;
                $info['provider'][] = $val->provider;
                $info['unitprice'][] = $val->unitprice;
                $info['total'][] = $val->total;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function remove_order($id)
    {
        if ($this->pst("DELETE FROM tbl_orderdetail WHERE idorder = :id", ['id' => $id], false))
        {
            $res = $this->pst("SELECT * FROM tbl_mttos WHERE idorder = :id", ['id' => $id], false);

            if (!empty($res))
            {
                if ($this->pst("DELETE FROM tbl_mttos WHERE idorder = :id", ['id' => $id], false))
                    $res = $this->pst("DELETE FROM tbl_orders WHERE idorder = :id", ['id' => $id], false);
                else
                    return false;
            }
            else
            {
                $res = $this->pst("DELETE FROM tbl_orders WHERE idorder = :id", ['id' => $id], false);
            }
        }
        else
        {
            $res = false;
        }

        return $res;
    }

    public function new_mtto($idorder, $type, $comment, $user, $nextkm, $idauto)
    {
        $arr_data = [
            'id' => $idorder,
            'type' => $type,
            'comment' => $comment,
            'user' => $user,
            'nextkm' => $nextkm,
            'idauto' => $idauto
        ];

        return $this->pst("CALL sp_newmtto(:id, :type, :comment, :user, :nextkm, :idauto)", $arr_data, false);
    }

    public function get_idmtto($idorder)
    {
        $res = $this->pst("SELECT idmtto FROM tbl_mttos WHERE idorder = :id", ['id' => $idorder]);

        return (!empty($res)) ? $res[0]->idmtto : false;
    }

    public function new_mttodetail($arr_data)
    {
        return $this->pst("CALL sp_newmttodetail(:idmtto, :idproduct, :quantity, :invoice, :unitprice, :idprovider)", $arr_data, false);
    }

    public function product_list()
    {
        $res = $this->pst("CALL sp_productlist()");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idproduct'][] = $val->idproduct;
                $info['product'][] = $val->product;
                $info['idcategory'][] = $val->idcategory;
                $info['category'][] = $val->category;
                $info['idstatus'][] = $val->idstatus;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function category_list()
    {
        $res = $this->pst("SELECT * FROM tbl_categories");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idcategory'][] = $val->idcategory;
                $info['name'][] = $val->name;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function new_category($name)
    {
        return $this->pst("INSERT INTO tbl_categories VALUES (NULL, :name)", ['name' => $name], false);
    }

    public function edit_category($arr_data)
    {
        return $this->pst("UPDATE tbl_categories SET name = :name WHERE idcategory = :idcat", $arr_data, false);
    }

    public function new_product($arr_data)
    {
        return $this->pst("INSERT INTO tbl_products VALUES (NULL, :idcat, :name, 1)", $arr_data, false);
    }

    public function edit_product($arr_data)
    {
        return $this->pst("UPDATE tbl_products SET name = :name, idcategory = :idcat WHERE idproduct = :id", $arr_data, false);
    }

    public function del_product($idprod)
    {
        return $this->pst("UPDATE tbl_products SET idstatus = 2 WHERE idproduct = :id", ['id' => $idprod], false);
    }

    public function mtactions_list()
    {
        $res = $this->pst("SELECT * FROM tbl_mtactions");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idaction'][] = $val->idaction;
                $info['action'][] = $val->action;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function new_mtaction($name)
    {
        return $this->pst("INSERT INTO tbl_mtactions VALUES (NULL, :name)", ['name' => $name], false);
    }

    public function edit_mtaction($arr_data)
    {
        return $this->pst("UPDATE tbl_mtactions SET action = :name WHERE idaction = :idaction", $arr_data, false);
    }

    public function level_list()
    {
        $res = $this->pst("SELECT * FROM tbl_levels");

        if (!empty($res))
        {
            $data = [];

            foreach ($res as $val)
            {
                $data['id'][] = $val->idlvl;
                $data['level'][] = $val->level;
            }

            return $data;
        }
        else
        {
            return false;
        }
    }

    public function info_alerts()
    {
        $res = $this->pst("SELECT * FROM tbl_alerts");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['idalert'] = $val->idalert;
                $info['info'] = $val->info;
                $info['val'] = $val->val;
                $info['alert'] = $val->alert;
            }

            return $info;
        }
        else
        {
            return false;
        }
    }

    public function edit_alert($arr_data)
    {
        return $this->pst("UPDATE tbl_alerts SET val = :rango, alert = :alerta WHERE idalert = 1", $arr_data, false);
    }

    public function bin_history($idauto, $year = 0)
    {
        $year = ($year == 0) ? date('Y') : $year;

        $res = $this->pst('CALL sp_binhistory(:idauto, :year)', ['idauto' => $idauto, 'year' => $year]);

        if (!empty($res))
        {
            $history = [];

            foreach ($res as $val)
            {
                $history['idbin'][] = $val->idbinnacle;
                $history['plate'][] = $val->plate;
                $history['brand'][] = $val->brand;
                $history['color'][] = $val->color;
                $history['region'][] = $val->region;
                $history['outdate'][] = $val->outdate;
                $history['outtime'][] = $val->outtime;
                $history['initkm'][] = $val->initkm;
                $history['entrydate'][] = (is_null($val->entrydate)) ? '-' : $val->entrydate;
                $history['entrytime'][] = (is_null($val->entrytime)) ? '-' : $val->entrytime;
                $history['endkm'][] = (is_null($val->endkm)) ? '-' : $val->endkm;
                $history['destination'][] = $val->destination;
                $history['idstatus'][] = $val->idstatus;
                $history['status'][] = ($val->idstatus == 6) ? '<span class="badge badge-educosuccess">Finalizado</span>' : '<span class="badge badge-educodanger">Pendiente</span>';
                $history['reason'][] = $val->reason;
                $history['idauto'][] = $val->idauto;
                $history['inituser'][] = $val->inituser;
                $history['initusername'][] = $val->initusername;
                $history['enduser'][] = $val->enduser;
                $history['endusername'][] = $val->endusername;
            }

            return $history;
        }
        else
        {
            return false;
        }
    }

    public function support_list()
    {
        $res = $this->pst("CALL sp_supportlist()");

        if (!empty($res))
        {
            $supports = [];

            foreach ($res as $val)
            {
                $supports['idsupport'][] = $val->idsupport;
                $supports['name'][] = $val->name;
                $supports['email'][] = $val->email;
                $supports['position'][] = $val->position;
                $supports['level'][] = $val->level;
                $supports['subject'][] = $val->subject;
                $supports['mssg'][] = $val->mssg;
                $supports['response'][] = $val->response;
                $supports['idstatus'][] = $val->idstatus;
                $supports['status'][] = $val->status;
            }

            return $supports;
        }
        else
        {
            return false;
        }
    }

    public function insert_comment($comment, $iduser)
    {
        if (!empty($comment))
        {
            $arr_data = [
                'idc' => null,
                'idu' => $iduser,
                'comment' => $comment
            ];

            $query = "INSERT INTO tbl_comments VALUES (:idc, :idu, :comment, CURDATE(), TIME_FORMAT(NOW(), '%H:%i'))";

            return $this->pst($query, $arr_data, false);
        }
        else
        {
            return false;
        }
    }

    public function del_comment($idcomment, $iduser)
    {
        $arr_data = [
            'idc' => $idcomment,
            'idu' => $iduser
        ];

        $res = $this->pst("SELECT * FROM tbl_comments WHERE idcomment = :idc AND iduser = :idu", $arr_data);

        if (!empty($res))
        {
            $this->pst("DELETE FROM tbl_comments WHERE idcomment = :idc", ['idc' => $idcomment], false);
        }
    }

    public function get_comments()
    {
        $res = $this->pst("SELECT * FROM tbl_comments ORDER BY idcomment DESC");

        if (!empty($res))
        {
            $comments = [];

            foreach ($res as $val)
            {
                $comments['id'][] = $val->idcomment;
                $comments['iduser'][] = $val->iduser;
                $comments['comment'][] = $val->comment;
                $comments['date'][] = $val->dcomment;
                $comments['time'][] = $val->tcomment;
            }

            return $comments;
        }
        else
        {
            return false;
        }
    }

    public function is_correct_mail($email)
    {
        $res = $this->pst("SELECT iduser FROM tbl_users WHERE email = :email", ['email' => $email]);
        return (!empty($res)) ? $res[0]->iduser : false;
    }

    public function recovery_req_on($iduser)
    {
        return $this->pst("UPDATE tbl_users SET forgetpass = 1 WHERE iduser = :id", ['id' => $iduser], false);
    }

    public function available_mail($email)
    {
        $res = $this->pst("SELECT * FROM tbl_users WHERE email = :email", ['email' => $email]);
        return (empty($res)) ? true : false;
    }

    public function register_user($name, $email, $position, $region, $lang, $level, $pwd, $accesstype)
    {
        $arr_data = [
            'name' => $name,
            'email' => $email,
            'pwd' => $pwd,
            'position' => $position,
            'region' => $region,
            'lang' => $lang,
            'level' => $level
        ];

        if (is_null($accesstype))
            return $this->pst("CALL sp_useregister(:name, :email, :pwd, :position, :region, :lang, :level, 0, 3, 'local')", $arr_data, false);
        else
            return $this->pst("CALL sp_useregister(:name, :email, :pwd, :position, :region, :lang, :level, 1, 1, 'social')", $arr_data, false);
    }

    protected function del_register($token)
    {
        $this->pst("DELETE FROM tbl_users WHERE token = :token", ['token' => $token], false);
    }

    public function new_support_request($subject, $mssg, $id)
    {
        $arr_data = [
            'id' => $id,
            'subject' => $subject,
            'mssg' => $mssg
        ];

        return $this->pst("CALL sp_supportrequest(:id, :subject, :mssg)", $arr_data, false);
    }

    public function history_request($iduser)
    {
        $query = "SELECT s.subject, s.mssg, s.response, s.idstatus, e.status FROM tbl_supports s INNER JOIN tbl_status e ON s.idstatus = e.idstatus WHERE iduser = :iduser";

        $res = $this->pst($query, ['iduser' => $iduser]);

        if (!empty($res))
        {
            $list = [];

            foreach ($res as $val)
            {
                $list['subject'][] = $val->subject;
                $list['mssg'][] = $val->mssg;
                $list['response'][] = $val->response;
                $list['idstatus'][] = $val->idstatus;
                $list['status'][] = $val->status;
            }

            return $list;
        }
        else
        {
            return false;
        }
    }

    public function log_history($iduser)
    {
        $res = $this->pst('CALL sp_loghistory(:iduser)', ['iduser' => $iduser]);

        if (!empty($res))
        {
            $history = [];

            foreach ($res as $val)
            {
                $history['idbinnacle'][] = $val->idbinnacle;
                $history['plate'][] = $val->plate;
                $history['brand'][] = $val->brand;
                $history['color'][] = $val->color;
                $history['region'][] = $val->region;
                $history['outdate'][] = $val->outdate;
                $history['outtime'][] = $val->outtime;
                $history['initkm'][] = $val->initkm;
                $history['entrydate'][] = $val->entrydate;
                $history['entrytime'][] = $val->entrytime;
                $history['endkm'][] = $val->endkm;
                $history['destination'][] = $val->destination;
                $history['status'][] = ($val->idstatus = 6) ? '<span class="badge badge-success">Finalizado</span>' : '<span class="badge badge-danger">Pendiente</span>';
            }

            return $history;
        }
        else
        {
            return false;
        }
    }

    public function charts($iduser, $year)
    {
        $res = $this->pst("SELECT MONTH(b.entrydate) AS 'mes', COUNT(*) AS 'total' FROM tbl_binnacles b WHERE YEAR(b.entrydate) = :year AND inituser = :iduser GROUP BY mes ASC", ['year' => $year, 'iduser' => $iduser]);

        if (!empty($res))
        {
            $data = [];

            $chart = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

            foreach ($res as $val)
            {
                $data['mes'][] = $val->mes;
                $data['total'][] = $val->total;
            }

            foreach ($data['mes'] as $i => $val)
            {
                $chart[$i] = $data['total'][$i];
            }

            return $chart;
        }
        else
        {
            return false;
        }
    }

/*
|--------------------------------------------------------------------------
| MIGRACIONES
|--------------------------------------------------------------------------
|
|
|
|
|
|
|
|
|
|
|
|
*/

    public function migracion_usuarios()
    {
        $res = $this->pst("SELECT * FROM tbl_usuarios");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $id = $this->pst("SELECT * FROM tbl_licencias WHERE idusuario = :id AND aprobacion = 1", ['id' => $val->idusuario]);


                $info['id'][] = $val->idusuario;
                $info['name'][] = $val->firstname." ".$val->secndname." ".$val->firstape." ".$val->secndape;
                $info['idNivelUsuario'][] = 2;
                $info['idRegion'][] = $val->idregion;
                $info['email'][] = $val->email;
                $info['pass'][] = $val->pass;
                $info['cargo'][] = $val->cargo;
                $info['token'][] = $val->token;
                $info['fechaToken'][] = $val->fechatoken;
                $info['idFoto'][] = $val->idfoto;
                $info['idEstado'][] = $val->idestado;
                $info['approval'][] = (!empty($id)) ? 1 : 0;
            }

            echo "INSERT INTO tbl_users VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['name'][$i]}', {$info['idNivelUsuario'][$i]}, {$info['idRegion'][$i]}, '{$info['email'][$i]}', '{$info['pass'][$i]}', '{$info['cargo'][$i]}', NULL, NULL, 'script', 1, 0, 1, {$info['idFoto'][$i]}, {$info['idEstado'][$i]}, {$info['approval'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_licencias()
    {
        $res = $this->pst("SELECT * FROM tbl_licencias");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idlicencia;
                $info['idusuario'][] = $val->idusuario;
                $info['dui'][] = $val->dui;
                $info['fechaexpd'][] = $val->fechaexpd;
                switch ($val->tipolicencia) {
                    case 'Licencia Particular':
                        $info['tipolicencia'][] = 1;
                    break;

                    case 'Licencia Liviana':
                        $info['tipolicencia'][] = 2;
                    break;

                    case 'Licencia Pesada':
                        $info['tipolicencia'][] = 3;
                    break;

                    case 'Licencia Pesada-T':
                        $info['tipolicencia'][] = 4;
                    break;

                    case 'Licencia de motociclista':
                        $info['tipolicencia'][] = 5;
                    break;

                    default:
                        $info['tipolicencia'][] = 1;
                    break;
                }
                $info['idestado'][] = $val->idestado;
                $info['aprobacion'][] = $val->aprobacion;
            }

            echo 'INSERT INTO tbl_licenses VALUES<br>';

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idusuario'][$i]}, '{$info['dui'][$i]}', '{$info['fechaexpd'][$i]}', {$info['tipolicencia'][$i]}, {$info['idestado'][$i]}, {$info['aprobacion'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_proveedores()
    {
        $res = $this->pst("SELECT * FROM tbl_proveedores");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idproveedor;
                $info['nombre'][] = $val->nombre;
                $info['contacto'][] = $val->contacto;
                $info['telefono'][] = $val->telefono;
                $info['idtipoproveedor'][] = $val->idtipoproveedor;
                $info['idpais'][] = $val->idpais;
            }

            echo "INSERT INTO tbl_providers VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['nombre'][$i]}', '{$info['contacto'][$i]}', '{$info['telefono'][$i]}', {$info['idtipoproveedor'][$i]}, {$info['idpais'][$i]}, 1),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_vehiculos()
    {
        $res = $this->pst("SELECT * FROM tbl_vehiculos");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idvehiculo;
                $info['numeroplaca'][] = $val->numeroplaca;
                $info['color'][] = $val->color;
                $info['idmarca'][] = $val->idmarca;
                $info['modelo'][] = $val->modelo;
                $info['anno'][] = $val->anno;
                $info['motor'][] = $val->motor;
                $info['chasis'][] = $val->chasis;
                $info['vin'][] = $val->vin;
                $info['idproveedor'][] = $val->idproveedor;
                $info['idregion'][] = $val->idregion;
                $info['proxmtto'][] = $val->proxmtto;
                $info['kilometraje'][] = $val->kilometraje;
                $info['idtipovehiculo'][] = $val->idtipovehiculo;
                $info['idestado'][] = $val->idestado;

                $status = $this->pst("SELECT idestado FROM tbl_bitacoras WHERE idvehiculo = $val->idvehiculo ORDER BY idbitacora DESC LIMIT 1");

                $info['statusbin'][] = (!empty($status)) ? $status[0]->idestado : 3;
            }

            echo "INSERT INTO tbl_autos VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['numeroplaca'][$i]}', '{$info['color'][$i]}', {$info['idmarca'][$i]}, '{$info['modelo'][$i]}', {$info['anno'][$i]}, '{$info['motor'][$i]}', '{$info['chasis'][$i]}', '{$info['vin'][$i]}', {$info['idproveedor'][$i]}, {$info['idregion'][$i]}, {$info['proxmtto'][$i]}, {$info['kilometraje'][$i]}, {$info['idtipovehiculo'][$i]}, {$info['idestado'][$i]}, {$info['statusbin'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_bitacoras()
    {
        $res = $this->pst("SELECT * FROM tbl_bitacoras");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idbitacora;
                $info['idvehiculo'][] = $val->idvehiculo;
                $info['kminicial'][] = $val->kminicial;
                $info['kmfinal'][] = (is_null($val->kmfinal)) ? 'NULL' : $val->kmfinal;
                $info['fechasalida'][] = $val->fechasalida;
                $info['fechaentrada'][] = (is_null($val->fechaentrada)) ? 'NULL' : "'{$val->fechaentrada}'";
                $info['horasalida'][] = $val->horasalida;
                $info['horaentrada'][] = (is_null($val->horaentrada)) ? 'NULL' : "'{$val->horaentrada}'";
                $info['idusuario'][] = $val->idusuario;
                $info['destino'][] = $val->destino;
                $info['motivo'][] = $val->motivo;
                $info['observaciones'][] = $val->observaciones;
                $info['idestado'][] = $val->idestado;
            }

            echo "INSERT INTO tbl_binnacles VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idvehiculo'][$i]}, {$info['kminicial'][$i]}, {$info['kmfinal'][$i]}, '{$info['fechasalida'][$i]}', {$info['fechaentrada'][$i]}, '{$info['horasalida'][$i]}', {$info['horaentrada'][$i]}, {$info['idusuario'][$i]}, {$info['idusuario'][$i]}, '{$info['destino'][$i]}', '{$info['motivo'][$i]}', '{$info['observaciones'][$i]}', {$info['idestado'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_hojasalidas()
    {
        $res = $this->pst("SELECT * FROM tbl_hojasalidas");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idhoja;
                $info['idbitacora'][] = $val->idbitacora;
                $info['nivelgas'][] = $val->nivelgas;
                $info['observacioninicial'][] = $val->observacioninicial;
                $info['observacionfinal'][] = $val->observacionfinal;
            }

            echo "INSERT INTO tbl_outputsheets VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idbitacora'][$i]}, '{$info['nivelgas'][$i]}', '{$info['observacioninicial'][$i]}', '{$info['observacionfinal'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_detallehojas()
    {
        $res = $this->pst("SELECT * FROM tbl_detallehojas");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idhoja;
                $info['iditem'][] = $val->iditem;
                $info['idestado'][] = $val->idestado;
            }

            echo "INSERT INTO tbl_sheetdetails VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['iditem'][$i]}, {$info['idestado'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_detallegolpes()
    {
        $res = $this->pst("SELECT dg.*, tg.descripcion FROM tbl_detallegolpes dg INNER JOIN tbl_tipogolpes tg ON dg.idTipo = tg.idTipo");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idhoja;
                $info['side'][] = $val->costado;
                $info['pic'][] = $val->foto;
                $info['idtype'][] = $val->idtipo;
                $info['obs'][] = $val->descripcion;
            }

            echo "INSERT INTO tbl_blowsdetail VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['side'][$i]}', '{$info['pic'][$i]}', {$info['idtype'][$i]}, '{$info['obs'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_combustible()
    {
        $res = $this->pst("SELECT * FROM tbl_combustibles");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idcombustible;
                $info['tipoingreso'][] = $val->tipoingreso;
                $info['ningreso'][] = $val->ningreso;
                $info['valor'][] = $val->valor;
                $info['fechaingreso'][] = $val->fechaingreso; //Fecha del vale
                $info['fecha'][] = $val->fecha; //Fecha de ingreso
                $info['hora'][] = $val->hora;
                $info['kmsalida'][] = $val->kmsalida;
                $info['kmentrada'][] = $val->kmentrada;
                $info['idproveedor'][] = $val->idproveedor;
                $info['idusuario'][] = $val->idusuario;
                $info['idvehiculo'][] = $val->idvehiculo;
            }

            echo "INSERT INTO tbl_gasrecords VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['tipoingreso'][$i]}', '{$info['ningreso'][$i]}', {$info['valor'][$i]}, '{$info['fecha'][$i]}', '{$info['fechaingreso'][$i]}', '{$info['hora'][$i]}', {$info['kmsalida'][$i]}, {$info['kmentrada'][$i]}, {$info['idproveedor'][$i]}, {$info['idusuario'][$i]}, {$info['idvehiculo'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_accionesmtto()
    {
        $res = $this->pst("SELECT * FROM tbl_accionesmant");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idaccion;
                $info['accion'][] = $val->accion;
            }

            echo "INSERT INTO tbl_mtactions VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['accion'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_preordenes()
    {
        $res = $this->pst("SELECT * FROM tbl_preordenes");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idpreorden;
                $info['tipopreorden'][] = $val->tipopreorden;
                $info['idvehiculo'][] = $val->idvehiculo;
                $info['kilometraje'][] = $val->kilometraje;
                $info['fechaentrada'][] = $val->fechaentrada;
                $info['fechasalida'][] = (!empty($val->fechasalida)) ? "'{$val->fechasalida}'" : "NULL";
                $info['comentarios'][] = $val->comentarios;
                $info['idusuario'][] = $val->idusuario;
                $info['idestado'][] = $val->idestado;
            }

            echo "INSERT INTO tbl_orders VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['tipopreorden'][$i]}', {$info['idvehiculo'][$i]}, {$info['kilometraje'][$i]}, '{$info['fechaentrada'][$i]}', {$info['fechasalida'][$i]}, '{$info['comentarios'][$i]}', {$info['idusuario'][$i]}, {$info['idestado'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_detallepreorden()
    {
        $res = $this->pst("SELECT * FROM tbl_detalleprinternas");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idpreorden;
                $info['accion'][] = $val->accion;
                $info['idestado'][] = $val->idestado;
            }

            echo "INSERT INTO tbl_orderdetail VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['accion'][$i]}', {$info['idestado'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_categorias()
    {
        $res = $this->pst("SELECT * FROM tbl_categorias");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idcategoria;
                $info['nombre'][] = $val->nombre;
            }

            echo "INSERT INTO tbl_categories VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['nombre'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_productos()
    {
        $res = $this->pst("SELECT * FROM tbl_productos");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idproducto;
                $info['idcategoria'][] = $val->idcategoria;
                $info['nombre'][] = $val->nombre;
            }

            echo "INSERT INTO tbl_products VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idcategoria'][$i]}, '{$info['nombre'][$i]}', 1),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_mantenimientos()
    {
        $res = $this->pst("SELECT * FROM tbl_mttos");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idmtto;
                $info['idpreorden'][] = $val->idpreorden;
                $info['idusuario'][] = $val->idusuario;
                $info['tipomtto'][] = $val->tipomtto;
                $info['comentario'][] = $val->comentario;
            }

            echo "INSERT INTO tbl_mttos VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idpreorden'][$i]}, {$info['idusuario'][$i]}, '{$info['tipomtto'][$i]}', '{$info['comentario'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_detallemttos()
    {
        $res = $this->pst("SELECT * FROM tbl_detallemttos");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idmtto;
                $info['idproducto'][] = $val->idproducto;
                $info['cantidad'][] = $val->cantidad;
                $info['numfactura'][] = $val->numfactura;
                $info['preciounitario'][] = $val->preciounitario;
                $info['idproveedor'][] = $val->idproveedor;
            }

            echo "INSERT INTO tbl_mtdetails VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idproducto'][$i]}, {$info['cantidad'][$i]}, '{$info['numfactura'][$i]}', {$info['preciounitario'][$i]}, {$info['idproveedor'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_alertas()
    {
        $res = $this->pst("SELECT * FROM tbl_alertas");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idalerta;
                $info['info'][] = $val->info;
                $info['valor'][] = $val->valor;
                $info['alerta'][] = $val->alerta;
            }

            foreach ($info['id'] as $i => $val)
            {
                echo "INSERT INTO tbl_alerts VALUES ({$val}, '{$info['info'][$i]}', {$info['valor'][$i]}, {$info['alerta'][$i]});<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_notificaciones()
    {
        $res = $this->pst("SELECT * FROM tbl_notificaciones");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->idalerta;
                $info['mensaje'][] = $val->mensaje;
                $info['detalles'][] = $val->detalles;
                $info['idvehiculo'][] = $val->idvehiculo;
            }

            echo "INSERT INTO tbl_notifications VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, '{$info['mensaje'][$i]}', '{$info['detalles'][$i]}', {$info['idvehiculo'][$i]}),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_cookies()
    {
        $res = $this->pst("SELECT * FROM tbl_cookies");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['email'][] = $val->email;
                $info['pass'][] = $val->pass;
                $info['sessiontoken'][] = $val->sessiontoken;
            }

            echo "INSERT INTO tbl_cookies VALUES<br>";

            foreach ($info['email'] as $i => $val)
            {
                echo "('{$val}', '{$info['pass'][$i]}', '{$info['sessiontoken'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_entradas()
    {
        $res = $this->pst("SELECT * FROM tbl_entradas");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->identrada;
                $info['idusuario'][] = $val->idusuario;
                $info['fecha'][] = $val->fecha;
            }

            echo "INSERT INTO tbl_inputs VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idusuario'][$i]}, '{$info['fecha'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }


    public function migracion_salidas()
    {
        $res = $this->pst("SELECT * FROM tbl_salidas");

        if (!empty($res))
        {
            $info = [];

            foreach ($res as $val)
            {
                $info['id'][] = $val->identrada;
                $info['idusuario'][] = $val->idusuario;
                $info['fecha'][] = $val->fecha;
            }

            echo "INSERT INTO tbl_outputs VALUES<br>";

            foreach ($info['id'] as $i => $val)
            {
                echo "({$val}, {$info['idusuario'][$i]}, '{$info['fecha'][$i]}'),<br>";
            }
        }
        else
        {
            echo 'Sin datos para mostrar...';
        }
    }
}