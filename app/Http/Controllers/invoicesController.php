<?php

namespace App\Http\Controllers;
use App\models\deletedinvoices;

// use App\models\payments;
// use App\models\deletedinvoices;



class invoicesController extends Controller {



	var $data = array();

	var $panelInit ;

	var $layout = 'dashboard';



	public function __construct(){

		if(app('request')->header('Authorization') != ""){

			$this->middleware('jwt.auth');

		}else{

			$this->middleware('authApplication');

		}



		$this->panelInit = new \DashboardInit();

		$this->data['panelInit'] = $this->panelInit;

		$this->data['breadcrumb']['Settings'] = \URL::to('/dashboard/languages');

		$this->data['users'] = $this->panelInit->getAuthUser();

		if(!isset($this->data['users']->id)){

			return \Redirect::to('/');

		}



		if(!$this->panelInit->hasThePerm('accounting')){

			exit;

		}

	}



	public function listAll($page = 1)

	{

		$toReturn = array();



		if($this->data['users']->role == "admin"){



			$toReturn['invoices'] = \DB::table('payments')

						->leftJoin('users', 'users.id', '=', 'payments.paymentStudent')

                        ->leftJoin('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

                        ->where('users.status', '!=', 2)

						->select(

                            'payments.id as id',

                            'payments.paymentTitle as paymentTitle',

                            'payments.paymentDescription as paymentDescription',

                            'payments.paymentAmount as paymentAmount',

                            'payments.paidAmount as paidAmount',

                            'payments.paymentStatus as paymentStatus',

                            'payments.paymentDate as paymentDate',

                            'payments.dueDate as dueDate',

                            'payments.paymentStudent as studentId',

                            \DB::raw('CONCAT(users.firstName, users.familyName) as fullName'),

                            'academic_year.yearTitle AS academicYear'

                        );



		}elseif($this->data['users']->role == "student"){



			$toReturn['invoices'] = \DB::table('payments')

						->where('paymentStudent',$this->data['users']->id)

						->leftJoin('users', 'users.id', '=', 'payments.paymentStudent')

                        ->leftJoin('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

                        ->where('users.status', '!=', 2)

						->select(

                            'payments.id as id',

                            'payments.paymentTitle as paymentTitle',

                            'payments.paymentDescription as paymentDescription',

                            'payments.paymentAmount as paymentAmount',

                            'payments.paidAmount as paidAmount',

                            'payments.paymentStatus as paymentStatus',

                            'payments.paymentDate as paymentDate',

                            'payments.dueDate as dueDate',

                            'payments.paymentStudent as studentId',

                            \DB::raw('CONCAT(users.firstName, users.familyName) as fullName'),

                            'academic_year.yearTitle AS academicYear'

                        );



		}elseif($this->data['users']->role == "parent"){



			$studentId = array();

			$parentOf = json_decode($this->data['users']->parentOf,true);

			if(is_array($parentOf)){

				while (list($key, $value) = each($parentOf)) {

					$studentId[] = $value['id'];

				}

			}

			$toReturn['invoices'] = \DB::table('payments')

						->whereIn('paymentStudent',$studentId)

						->leftJoin('users', 'users.id', '=', 'payments.paymentStudent')

                        ->leftJoin('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

						->select(

                            'payments.id as id',

                            'payments.paymentTitle as paymentTitle',

                            'payments.paymentDescription as paymentDescription',

                            'payments.paymentAmount as paymentAmount',

                            'payments.paidAmount as paidAmount',

                            'payments.paymentStatus as paymentStatus',

                            'payments.paymentDate as paymentDate',

                            'payments.dueDate as dueDate',

                            'payments.paymentStudent as studentId',

                            \DB::raw('CONCAT(users.firstName, users.familyName) as fullName'),

                            'academic_year.yearTitle AS academicYear'

                        );

		}



		if(\Input::has('searchInput')){

			$searchInput = \Input::get('searchInput');

			if(is_array($searchInput)){



				if(isset($searchInput['dueInv']) AND $searchInput['dueInv'] == true){

					$toReturn['invoices'] = $toReturn['invoices']->where('dueDate','<',time())->where('paymentStatus','!=','1');

				}



				if(isset($searchInput['text']) AND strlen($searchInput['text']) > 0 ){

					$keyword = $searchInput['text'];

					$toReturn['invoices'] = $toReturn['invoices']->where(function($query) use ($keyword){

						$query->where('payments.paymentTitle','LIKE','%'.$keyword.'%');

						$query->orWhere('payments.paymentDescription','LIKE','%'.$keyword.'%');

						$query->orWhere('firstName','LIKE','%'.$keyword.'%');

						$query->orWhere('familyName','LIKE','%'.$keyword.'%');

					});

				}



                if (isset($searchInput['studentId']) AND $searchInput['studentId'] != '') {

                    $toReturn['invoices'] = $toReturn['invoices']->where('users.studentRollId', $searchInput['studentId']);

                }



                if (isset($searchInput['passport']) AND $searchInput['passport'] != '') {

                    $toReturn['invoices'] = $toReturn['invoices']->where('users.passport', $searchInput['passport']);

                }



                if(isset($searchInput['class']) AND $searchInput['class'] != "" ){

					$toReturn['invoices'] = $toReturn['invoices']->where('users.studentClass',$searchInput['class']);

				}



				if(isset($searchInput['section']) AND $searchInput['section'] != "" ){

					$toReturn['invoices'] = $toReturn['invoices']->where('users.studentSection',$searchInput['section']);

				}



				if(isset($searchInput['paymentStatus']) AND $searchInput['paymentStatus'] != ""){

					$toReturn['invoices'] = $toReturn['invoices']->where('users.paymentStatus',$searchInput['paymentStatus']);

				}



				if(isset($searchInput['fromDate']) AND $searchInput['fromDate'] != ""){

					$searchInput['fromDate'] = $this->panelInit->date_to_unix($searchInput['fromDate']);

					$toReturn['invoices'] = $toReturn['invoices']->where('paymentDate','>=',$searchInput['fromDate']);

				}



				if(isset($searchInput['toDate']) AND $searchInput['toDate'] != ""){

					$searchInput['toDate'] = $this->panelInit->date_to_unix($searchInput['toDate']);

					$toReturn['invoices'] = $toReturn['invoices']->where('paymentDate','<=',$searchInput['toDate']);

				}

			}

		}



		$toReturn['totalItems'] = $toReturn['invoices']->count();

		$toReturn['invoices'] = $toReturn['invoices']->orderBy('id','DESC')->take('20')->skip(20* ($page - 1) )->get();



		foreach ($toReturn['invoices'] as $key => $value) {

			$toReturn['invoices'][$key]->paymentDate = $this->panelInit->unix_to_date($toReturn['invoices'][$key]->paymentDate);

			$toReturn['invoices'][$key]->dueDate = $this->panelInit->unix_to_date($toReturn['invoices'][$key]->dueDate);

			$toReturn['invoices'][$key]->paymentAmount = $toReturn['invoices'][$key]->paymentAmount + ($this->panelInit->settingsArray['paymentTax']*$toReturn['invoices'][$key]->paymentAmount) /100;

		}



		$return['currency_symbol'] = $this->panelInit->settingsArray['currency_symbol'];



		$toReturn['classes'] = $classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get()->toArray();

		$classArray = array();

		$classesIds = array();

		while (list(, $value) = each($classes)) {

			$classesIds[] = $value['id'];

			$classArray[$value['id']] = $value['className'];

		}

		$toReturn['transports'] =  \transportation::get()->toArray();



		$sectionArray = array();

		if(count($classesIds) > 0){

			$toReturn['sections'] = $sections = \sections::whereIn('classId',$classesIds)->get()->toArray();

			while (list(, $value) = each($sections)) {

				$sectionArray[$value['id']] = $value['sectionName'] . " - ". $value['sectionTitle'];

			}

		}



		$toReturn['fee_types'] = \fee_type::get();

		$max_id = 0;

		foreach ($toReturn['invoices'] as $key => $value) {

			if($max_id<$toReturn['invoices'][$key]->id) $max_id=$toReturn['invoices'][$key]->id;

		}

		$max_id+=1;

		$toReturn['max_id'] = $max_id;


		return $toReturn;

	}



	public function delete($id){

		
		
		if($this->data['users']->role != "admin") exit;
		if($this->data['users']->customPermissionsType != "full") exit;
		

		if ( $postDelete = \payments::where('id', $id)->first()->toArray() ) {
			
			// first save the deleted invoice in deletedinvoices table
			//start
			
			$arry=array();

    		$deletedInvoices = new deletedinvoices;

			foreach($postDelete as $item){

				array_push($arry, $item);

			}
			
			date_default_timezone_set('Asia/Kuwait');
			$del_date=date("m/d/Y");
			$del_date=$this->panelInit->date_to_unix($del_date);
			
			$del_time=date("h:i:s");
			$username=$this->data['users']->username;
		

			$deletedInvoices->delid = $arry[0];
			$deletedInvoices->paymentTitle = $arry[1];
			$deletedInvoices->paymentDescription = $arry[2];
			$deletedInvoices->paymentStudent = $arry[3];
			$deletedInvoices->paymentRows = $arry[4];
			$deletedInvoices->paymentAmount = $arry[5];
			$deletedInvoices->paymentStatus = $arry[6];
			$deletedInvoices->paymentDate = $arry[7];
			$deletedInvoices->dueDate = $arry[8];
			$deletedInvoices->paymentUniqid = $arry[9];
			$deletedInvoices->paymentSuccessDetails = $arry[10];
			$deletedInvoices->paidMethod = $arry[11];
			$deletedInvoices->paidTime = $arry[12];
			$deletedInvoices->fee_type_id = $arry[13];

			$deletedInvoices->delDate = $del_date;
			$deletedInvoices->delTime = $del_time;
			$deletedInvoices->username = $username;

			$deletedInvoices->save();
			
			
			$post_Delete = \payments::where('id', $id)->first();

            $post_Delete->delete();

            return $this->panelInit->apiOutput(true,$this->panelInit->language['delPayment'],$this->panelInit->language['paymentDel']);

        }else{

            return $this->panelInit->apiOutput(false,$this->panelInit->language['delPayment'],$this->panelInit->language['paymentNotExist']);

        }

	}



	public function create(){

		if($this->data['users']->role != "admin") exit;

		$craetedPayments = array();

		$studentClass = \Input::get('paymentStudent');

		if(!is_array($studentClass)){

			return $this->panelInit->apiOutput(false,$this->panelInit->language['addPayment'],"No students are selected");

		}



        $fee_type_id = \Input::get('paymentDescription');

        $fee_types = \fee_type::where('id', $fee_type_id)->first();

		$paymentTitle = $fee_types['feeCode'].'_'.(\payments::max('id') + 1);

		// return $this->panelInit->apiOutput(false,$this->panelInit->language['addPayment'],$paymentTitle);



		while (list($key, $value) = each($studentClass)) {

			if($value['id'] == "" || $value['id'] == "0"){

				continue;

			}



			$payments = new \payments();

			$payments->paymentTitle = $paymentTitle;

            $payments->paymentDescription = $fee_types['feeTitle'];

            $payments->fee_type_id = $fee_type_id;

			$payments->paymentStudent = $value['id'];



			if(\Input::has('paymentRows')){

				$payments->paymentRows = json_encode(\Input::get('paymentRows'));



				$paymentAmount = 0;

				$paymentRows = \Input::get('paymentRows');

				while (list($key, $value) = each($paymentRows)) {

					$paymentAmount += $value['amount'];

				}

			}else{

				$paymentRows = array();

				$payments->paymentRows = json_encode($paymentRows);

				$paymentAmount = 0;

			}



			$payments->paymentAmount = $paymentAmount;

			$tax = ($this->panelInit->settingsArray['paymentTax']*$paymentAmount) /100;

			$payments->paymentDate = $this->panelInit->date_to_unix(\Input::get('paymentDate'));

			$payments->dueDate = $this->panelInit->date_to_unix(\Input::get('dueDate'));



			$payments->paymentUniqid = uniqid();

			$payments->paymentStatus = \Input::get('paymentStatus');

			if(\Input::get('paymentStatus') == 1){

				$payments->paidAmount = $paymentAmount+$tax;

				if(\Input::has('paidMethod')){

					$payments->paidMethod = \Input::get('paidMethod');

				}

				if(\Input::has('paidTime')){

					$payments->paidTime = $this->panelInit->date_to_unix(\Input::get('paidTime'));

				}

			}

			$payments->save();

			// return $this->panelInit->apiOutput(false,$this->panelInit->language['addPayment'],$payments->paidTime);

			$this->panelInit->mobNotifyUser('users',$value['id'], $this->panelInit->language['newPaymentNotif']);



			$payments->paymentDate = \Input::get('paymentDate');

			$payments->dueDate = \Input::get('dueDate');

			$craetedPayments[] = $payments->toArray();

		}



		return $this->panelInit->apiOutput(true,$this->panelInit->language['addPayment'],$this->panelInit->language['paymentCreated'],$craetedPayments );

	}

	public function test(){

		$countryInfo = \DB::table('users')
		   	->leftJoin('country', 'users.country', '=', 'country.id')
				->leftJoin('payments', 'users.id', '=', 'paymentStudent')
				->where('payments.id',7440)
				->select('country.countryTitle')
				->first();

		dump($countryInfo->countryTitle);
		die();
	}

	function invoice($id){


		$return = array();

		$countryInfo = \DB::table('users')
		   	->leftJoin('country', 'users.country', '=', 'country.id')
				->leftJoin('payments', 'users.id', '=', 'paymentStudent')
				->where('payments.id',$id)
				->select('country.countryTitle')
				->first();

        $return['countryInfo'] = $countryInfo;
		$return['countryInfoid'] = $id;
		$return['payment'] = \payments::where('id',$id)->first()->toArray();

		$return['payment']['paymentDate'] = $this->panelInit->unix_to_date($return['payment']['paymentDate']);

		if($return['payment']['dueDate'] < time()){

		   $return['payment']['isDueDate'] = true;

		}

		$return['payment']['dueDate'] = $this->panelInit->unix_to_date($return['payment']['dueDate']);

		if($return['payment']['paymentStatus'] == "1"){

			$return['payment']['paidTime'] = $this->panelInit->unix_to_date($return['payment']['paidTime']);

		}

		$return['payment']['paymentRows'] = json_decode($return['payment']['paymentRows'],true);

		$return['siteTitle'] = $this->panelInit->settingsArray['siteTitle'];

		$return['baseUrl'] = \URL::to('/');

		$return['address'] = $this->panelInit->settingsArray['address'];

		$return['address2'] = $this->panelInit->settingsArray['address2'];

		$return['systemEmail'] = $this->panelInit->settingsArray['systemEmail'];

		$return['phoneNo'] = $this->panelInit->settingsArray['phoneNo'];

		$return['paypalPayment'] = $this->panelInit->settingsArray['paypalPayment'];

		$return['currency_code'] = $this->panelInit->settingsArray['currency_code'];

		$return['currency_symbol'] = $this->panelInit->settingsArray['currency_symbol'];

		$return['paymentTax'] = $this->panelInit->settingsArray['paymentTax'];

		if($countryInfo->countryTitle=="Saudi Arabia"){
				$return['amountTax'] = 0;
				$return['totalWithTax'] = $return['payment']['paymentAmount'];
				$return['payment']['paidAmount']=$return['totalWithTax'];
				
	        	
		}else{
			$return['amountTax'] = ($this->panelInit->settingsArray['paymentTax']*$return['payment']['paymentAmount']) /100;
			$return['totalWithTax'] = $return['payment']['paymentAmount'] + $return['amountTax'];

		}

		$return['subtotal'] = $return['payment']['paymentAmount'];

		$return['pendingAmount'] = $return['totalWithTax'] - $return['payment']['paidAmount'];

		$return['user'] = \User::where('users.id',$return['payment']['paymentStudent'])->leftJoin('classes','users.studentClass','=','classes.id')->leftJoin('sections','users.studentSection','=','sections.id')->select('users.*','classes.className','sections.sectionName','sections.sectionTitle')->first()->toArray();

		$return['paypalEnabled'] = $this->panelInit->settingsArray['paypalEnabled'];

		$return['2coEnabled'] = $this->panelInit->settingsArray['2coEnabled'];

		$return['payumoneyEnabled'] = $this->panelInit->settingsArray['payumoneyEnabled'];

		$return['fullname'] = $this->data['users']->fullName;

		$return['collection'] = \paymentsCollection::where('invoiceId',$id)->get()->toArray();

		while (list($key, $value) = each($return['collection'])) {

			$return['collection'][$key]['collectionDate'] = $this->panelInit->unix_to_date($return['collection'][$key]['collectionDate']);

		}

		$return['country'] = \User::where('users.id',$return['payment']['paymentStudent'])->leftJoin('country','users.country','=','country.id')->select('country.countryTitle')->get()->toArray();

		$return['actived_year'] = \academic_year::where('isDefault','1')->get()->toArray();

		$_academic_year= \academic_year::where('isDefault','1')->select('yearTitle')->get()->first();
		
// 		$user_id = \payments::where('payments.id',$id)->select('paymentStudent')->get()->first();
		$user_id = \DB::table('users')
		   		->leftJoin('payments', 'users.id', '=', 'paymentStudent')
				->where('payments.id',$id)
				->select('users.studentRollId')
				->first();
		$aaa = array();

		// $tmp_academic_year = explode ('-',$_academic_year->yearTitle);
		// $from_date='04/01/'.$tmp_academic_year[0];
		// $end_date='03/31/'.$tmp_academic_year[1];
		// $from_date=$this->panelInit->date_to_unix($from_date);
		// $end_date=$this->panelInit->date_to_unix($end_date);

// 		$return['abcdef']=$user_id;

		$return['tableData'] = $this -> invoice_student($_academic_year->yearTitle, $user_id->studentRollId);
        
		
		return $return;

	}

	public function invoice_student($_academic_year, $userid){

		
		$return = array();
		$return_sub = array();
		$return_all = array();
		$tmp_academic_year = array();
		$from_date="";
		$end_date="";

		
		$tmp_academic_year = explode ('-',$_academic_year);
		$from_date='04/01/'.$tmp_academic_year[0];
		$end_date='03/31/'.$tmp_academic_year[1];
		$from_date=$this->panelInit->date_to_unix($from_date);
		$end_date=$this->panelInit->date_to_unix($end_date);
		$return_all['from_academic_year']= $from_date;
		$return_all['end_academic_year']= $end_date;


		$monthName = array();
		$GroupName = array();
		
		$feeGroupName = \fee_group::select('invoice_prefix')->get();
		
		$monthName = array("APRIL", "MAY", "JUNE", "JULY", "AUG", "SEP", "OCT", "NOV", "DEC","JAN", "FEB", "MARCH");
		
		foreach($feeGroupName  as $key => $value){
            array_push($GroupName, $value->invoice_prefix);
		}

		 
		$receipt = \DB::table('users')

		->join('payments', 'users.id', '=', 'payments.paymentStudent')

		->select('payments.paymentRows')

		->where('users.role', 'student')

		->where('payments.paidTime','>=',$from_date)
		
		->where('payments.paidTime','<=',$end_date)

		->where('users.studentRollId',$userid);


		$receipt = $receipt->get();
		$sss=array();
	   
		$paymentRows_array=array();
		$all_pay=0;
		

		$other=0;

		

		foreach ($receipt as $key => $id_arry) {
					$paymentRows="";
					$paymentRows_array=[];
					$return_sub=[];
				

					$paymentRows = $receipt[$key]->paymentRows;
					$paymentRows_array=json_decode($paymentRows,true);
					if(is_array($paymentRows_array)){
						foreach ($paymentRows_array as $key1 => $value1) {
							$str_tmp="";
							$str_tmp= $paymentRows_array[$key1]["title"];	  
							$check_groupname=0;
							
							foreach($GroupName as $value_GN){
								$tmp1=0;
								foreach($monthName as $value_MN){
								    $sub_name = $value_GN."-".$value_MN;
								    if($str_tmp==$sub_name){
										if(isset($return["$value_GN"]["$value_MN"])){
											$tmp=$return["$value_GN"]["$value_MN"];
										}else{
											$tmp=0;
										}
										
										$return["$value_GN"]["$value_MN"]=$tmp+$paymentRows_array[$key1]["amount"];
										
										
										$tmp1=$tmp1+$paymentRows_array[$key1]["amount"];
										$check_groupname=1;
										break;
									}
								}
								
							}
							
							if($check_groupname==0){
								$other=$other+$paymentRows_array[$key1]["amount"];
							}
						}	
					}

		}
		$total=0;
        $return_arry=array();
		foreach($return as $key => $value){
			$temp=array() ;
			$sub_total=0;
			array_push($temp, $key);
			foreach($monthName as $mname){
				$chk=0;
				
				foreach($value as $key_month => $value1){
					if($mname==$key_month){
					   array_push($temp, $value1);
					   $sub_total+=$value1;
					   $chk=1;
					   break;
					}
				}
				if($chk==0){
					array_push($temp,"");
				}

			}
			array_push($temp, $sub_total);
			array_push($return_arry, $temp);
			$total+=$sub_total;
		}
		$total+=$other;
		$temp=array();
		array_push($temp, "other");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, $other);

		array_push($return_arry, $temp);

		$temp=array();
		array_push($temp, "Total");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, "");
		array_push($temp, $total);

		array_push($return_arry, $temp);

		return $return_arry;
           
}

	function fetch($id){
        
        if($this->data['users']->role != "admin") exit;
        if($this->data['users']->customPermissionsType != "full") exit;
		
		$payments = \payments::where('id',$id)->first()->toArray();

		$payments['paymentDate'] = $this->panelInit->unix_to_date($payments['paymentDate']);

		$payments['dueDate'] = $this->panelInit->unix_to_date($payments['dueDate']);
		
		$payments['paidTime'] = $this->panelInit->unix_to_date($payments['paidTime']);

		$payments['paymentRows'] = json_decode($payments['paymentRows'],true);

		if(!is_array($payments['paymentRows'])){

			$payments['paymentRows'] = array();

			$payments['paymentRows'][] = array('title'=>$payments['paymentDescription'],'amount'=>$payments['paymentAmount']);

		}

		$monthName = array();
		$arry_paymentTitle = array();
		
		$feeGroupName = \fee_group::select('invoice_prefix')->get();
		
		$monthName = array("JAN", "FEB", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUG", "SEP", "OCT", "NOV", "DEC");
		
		foreach($feeGroupName  as $key => $value){
			$groupname=$value->invoice_prefix;
			
			foreach($monthName as $value1){
			   $sub_name = $groupname."-".$value1;
			   array_push($arry_paymentTitle, $sub_name);
			}
			
		}

		// $arry_paymentTitle=array("TF-JAN","TF-FEB","TF-MARCH","TF-APRIL","TF-MAY","TF-JUNE","TF-JULY","TF-AUG","TF-SEP","TF-OCT","TF-NOV","TF-DEC",
        //                       "BF-JAN","BF-FEB","BF-MARCH","BF-APRIL","BF-MAY","BF-JUNE","BF-JULY","BF-AUG","BF-SEP","BF-OCT","BF-NOV","BF-DEC",
        //                       "MF-JAN","MF-FEB","MF-MARCH","MF-APRIL","MF-MAY","MF-JUNE","MF-JULY","MF-AUG","MF-SEP","MF-OCT","MF-NOV","MF-DEC",
        //                       "OF-JAN","OF-FEB","OF-MARCH","OF-APRIL","OF-MAY","OF-JUNE","OF-JULY","OF-AUG","OF-SEP","OF-OCT","OF-NOV","OF-DEC",
        //                       "OF-ADM-JAN","OF-ADM-FEB","OF-ADM-MARCH","OF-ADM-APRIL","OF-ADM-MAY","OF-ADM-JUNE","OF-ADM-JULY","OF-ADM-AUG","OF-ADM-SEP","OF-ADM-OCT","OF-ADM-NOV","OF-ADM-DEC",
        //                       "OF-ANN-JAN","OF-ANN-FEB","OF-ANN-MARCH","OF-ANN-APRIL","OF-ANN-MAY","OF-ANN-JUNE","OF-ANN-JULY","OF-ANN-AUG","OF-ANN-SEP","OF-ANN-OCT","OF-ANN-NOV","OF-ANN-DEC",
        //                       "OF-AF-JAN","OF-AF-FEB","OF-AF-MARCH","OF-AF-APRIL","OF-AF-MAY","OF-AF-JUNE","OF-AF-JULY","OF-AF-AUG","OF-AF-SEP","OF-AF-OCT","OF-AF-NOV","OF-AF-DEC",
        //                       "OF-PRO-JAN","OF-PRO-FEB","OF-PRO-MARCH","OF-PRO-APRIL","OF-PRO-MAY","OF-PRO-JUNE","OF-PRO-JULY","OF-PRO-AUG","OF-PRO-SEP","OF-PRO-OCT","OF-PRO-NOV","OF-PRO-DEC"
		// );
		
		$payments['arryTitle']=$arry_paymentTitle;


		return $payments;

	}

	function fetch_add(){

		$payments = array();
		$monthName = array();
		$arry_paymentTitle = array();
		
		$feeGroupName = \fee_group::select('invoice_prefix')->get();
		
		$monthName = array("JAN", "FEB", "MARCH", "APRIL", "MAY", "JUNE", "JULY", "AUG", "SEP", "OCT", "NOV", "DEC");
		
		foreach($feeGroupName  as $key => $value){
			$groupname=$value->invoice_prefix;
			
			foreach($monthName as $value1){
			   $sub_name = $groupname."-".$value1;
			   array_push($arry_paymentTitle, $sub_name);
			}
			
		}
		

		
		// $arry_paymentTitle=array("TF-JAN","TF-FEB","TF-MARCH","TF-APRIL","TF-MAY","TF-JUNE","TF-JULY","TF-AUG","TF-SEP","TF-OCT","TF-NOV","TF-DEC",
		// 						"BF-JAN","BF-FEB","BF-MARCH","BF-APRIL","BF-MAY","BF-JUNE","BF-JULY","BF-AUG","BF-SEP","BF-OCT","BF-NOV","BF-DEC",
		// 						"MF-JAN","MF-FEB","MF-MARCH","MF-APRIL","MF-MAY","MF-JUNE","MF-JULY","MF-AUG","MF-SEP","MF-OCT","MF-NOV","MF-DEC",
		// 						"OF-JAN","OF-FEB","OF-MARCH","OF-APRIL","OF-MAY","OF-JUNE","OF-JULY","OF-AUG","OF-SEP","OF-OCT","OF-NOV","OF-DEC",
		// 						"OF-ADM-JAN","OF-ADM-FEB","OF-ADM-MARCH","OF-ADM-APRIL","OF-ADM-MAY","OF-ADM-JUNE","OF-ADM-JULY","OF-ADM-AUG","OF-ADM-SEP","OF-ADM-OCT","OF-ADM-NOV","OF-ADM-DEC",
		// 						"OF-ANN-JAN","OF-ANN-FEB","OF-ANN-MARCH","OF-ANN-APRIL","OF-ANN-MAY","OF-ANN-JUNE","OF-ANN-JULY","OF-ANN-AUG","OF-ANN-SEP","OF-ANN-OCT","OF-ANN-NOV","OF-ANN-DEC",
		// 						"OF-AF-JAN","OF-AF-FEB","OF-AF-MARCH","OF-AF-APRIL","OF-AF-MAY","OF-AF-JUNE","OF-AF-JULY","OF-AF-AUG","OF-AF-SEP","OF-AF-OCT","OF-AF-NOV","OF-AF-DEC",
		// 						"OF-PRO-JAN","OF-PRO-FEB","OF-PRO-MARCH","OF-PRO-APRIL","OF-PRO-MAY","OF-PRO-JUNE","OF-PRO-JULY","OF-PRO-AUG","OF-PRO-SEP","OF-PRO-OCT","OF-PRO-NOV","OF-PRO-DEC"
		// );

		$payments['arryTitle']=$arry_paymentTitle;
		return $payments;
	

	}



	function edit($id){

		if($this->data['users']->role != "admin") exit;
		
	

		$payments = \payments::find($id);

		$payments->paymentTitle = \Input::get('paymentTitle');

		if(\Input::has('paymentDescription')){

			$payments->paymentDescription = \Input::get('paymentDescription');

		}



		if(\Input::has('paymentRows')){

			$payments->paymentRows = json_encode(\Input::get('paymentRows'));



			$paymentAmount = 0;

			$paymentRows = \Input::get('paymentRows');

			while (list($key, $value) = each($paymentRows)) {

				$paymentAmount += $value['amount'];

			}

		}else{

			$paymentRows = array();

			$payments->paymentRows = json_encode($paymentRows);

			$paymentAmount = 0;

		}



		$payments->paymentAmount = $paymentAmount;

		$payments->paymentDate = $this->panelInit->date_to_unix(\Input::get('paymentDate'));

		$payments->dueDate = $this->panelInit->date_to_unix(\Input::get('dueDate'));

        $payments->paidTime = $this->panelInit->date_to_unix(\Input::get('paidTime'));

        if (\Input::has('adjustment')) {

            $payments->paymentStatus = 3;

        }



		$payments->save();



		$payments->paymentDate = \Input::get('paymentDate');

		$payments->dueDate = \Input::get('dueDate');



		return $this->panelInit->apiOutput(true,$this->panelInit->language['editPayment'],$this->panelInit->language['paymentModified'],$payments->toArray() );

	}



	function collect($id){

		if($this->data['users']->role != "admin") exit;

		$payments = \payments::where('id',$id);

		if($payments->count() == 0){

			return;

		}

		$payments = $payments->first();



		$amountTax = ($this->panelInit->settingsArray['paymentTax']*$payments->paymentAmount) /100;

		$totalWithTax = $payments->paymentAmount + $amountTax;

		$pendingAmount = $totalWithTax - $payments->paidAmount;



		if(bccomp(\Input::get('collectionAmount'), $pendingAmount,10) == 1){

			return $this->panelInit->apiOutput(false,"Invoice Collection","Collection amount is greater that invoice pending amount");

		}



		$paymentsCollection = new \paymentsCollection();

		$paymentsCollection->invoiceId = $id;

		$paymentsCollection->collectionAmount = \Input::get('collectionAmount');

		$paymentsCollection->collectionDate = $this->panelInit->date_to_unix(\Input::get('collectionDate'));

		$paymentsCollection->collectionMethod = \Input::get('collectionMethod');

		if(\Input::has('collectionNote')){

			$paymentsCollection->collectionNote = \Input::get('collectionNote');

		}

		$paymentsCollection->collectedBy = $this->data['users']->id;

		$paymentsCollection->save();



		$payments->paidAmount = $payments->paidAmount+$paymentsCollection->collectionAmount;

		if($payments->paidAmount >= $totalWithTax){

			$payments->paymentStatus = 1;

		}else{

			$payments->paymentStatus = 2;

		}

		$payments->paidMethod = \Input::get('collectionMethod');

		$payments->paidTime = $this->panelInit->date_to_unix(\Input::get('collectionDate'));

		$payments->save();



		$payments->paymentAmount = $totalWithTax;



		return $this->panelInit->apiOutput(true,"Invoice Collection","Collection completed successfully",$payments->toArray());

	}



	function revert($id){

		if($this->data['users']->role != "admin") exit;

		$paymentsCollection = \paymentsCollection::where('id',$id);

		if($paymentsCollection->count() == 0){

			return;

		}

		$paymentsGet = $paymentsCollection->first();

		$invoice = $paymentsGet->invoiceId;

		$paymentsCollection = $paymentsCollection->delete();



		//recalculate

		$totalPaid = 0;

		$paymentsCollection = \paymentsCollection::where('invoiceId',$invoice)->get();

		foreach ($paymentsCollection as $key => $value) {

			$totalPaid += $value['collectionAmount'];

		}



		$payments = \payments::where('id',$invoice);

		if($payments->count() == 0){

			return;

		}

		$payments = $payments->first();



		$amountTax = ($this->panelInit->settingsArray['paymentTax']*$payments->paymentAmount) /100;

		$totalWithTax = $payments->paymentAmount + $amountTax;



		if($totalPaid >= $totalWithTax){

			$payments->paymentStatus = 1;

		}elseif ($totalPaid == 0) {

			$payments->paymentStatus = 0;

		}else{

			$payments->paymentStatus = 2;

		}

		$payments->paidAmount = $totalPaid;

		$payments->save();



		return $this->panelInit->apiOutput(true,"Revert Invoice Collection","Collection reverted successfully",$payments->toArray());

	}



	function paymentSuccess($uniqid){

		$payments = \payments::where('paymentUniqid',$uniqid)->first();

		if(\Input::get('verify_sign')){

			$payments->paymentStatus = 1;

			$payments->paymentSuccessDetails = json_encode(\Input::all());

			$payments->save();

		}

		return \Redirect::to('/#/payments');

	}



	function PaymentData($id){

		if($this->data['users']->role != "admin") exit;

		$payments = \payments::where('id',$id)->first();

		if($payments->paymentSuccessDetails == ""){

			return $this->panelInit->apiOutput(false,$this->panelInit->language['paymentDetails'],$this->panelInit->language['noPaymentDetails'] );

		}else{

			return $this->panelInit->apiOutput(true,null,null,json_decode($payments->paymentSuccessDetails,true) );

		}

	}



	function paymentFailed(){

		return \Redirect::to('/#/payments');

	}



	public function searchStudents($student){

		$students = \User::where('role','student')

                ->where(function($query) use ($student) {

                    $query->where('firstName','like','%'.$student.'%')

                            ->orWhere('secondName','like','%'.$student.'%')

                            ->orWhere('thirdName','like','%'.$student.'%')

                            ->orWhere('familyName','like','%'.$student.'%')

                            ->orWhere('passport','like','%'.$student.'%');

                })->orWhere('studentRollId',$student)->orWhere('username','like','%'.$student.'%')->orWhere('email','like','%'.$student.'%')->get();

		$retArray = array();

		foreach ($students as $student) {

			$retArray[$student->id] = array("id"=>$student->id,"name"=>$student->firstName.' '.$student->familyName,"email"=>$student->email);

		}

		return json_encode($retArray);

	}



	function export($type){

		if($this->data['users']->role != "admin") exit;

		if($type == "excel"){



			$return['currency_symbol'] = $this->panelInit->settingsArray['currency_symbol'];



			$data = array(1 => array ('Invoice ID','Title','Student','Amount','Paid Amount','Date','Due Date','Status'));



			$toReturn['invoices'] = \DB::table('payments')

						->leftJoin('users', 'users.id', '=', 'payments.paymentStudent')

						->select('payments.id as id',

						'payments.paymentTitle as paymentTitle',

						'payments.paymentDescription as paymentDescription',

						'payments.paymentAmount as paymentAmount',

						'payments.paidAmount as paidAmount',

						'payments.paymentStatus as paymentStatus',

						'payments.paymentDate as paymentDate',

						'payments.dueDate as dueDate',

						'payments.paymentStudent as studentId',

						'users.fullName as fullName');

			$toReturn['totalItems'] = $toReturn['invoices']->count();

			$toReturn['invoices'] = $toReturn['invoices']->orderBy('id','DESC')->get();



			foreach ($toReturn['invoices'] as $key => $value) {

				$value->paymentDate = $this->panelInit->unix_to_date($toReturn['invoices'][$key]->paymentDate);

				$value->dueDate = $this->panelInit->unix_to_date($toReturn['invoices'][$key]->dueDate);

				$value->paymentAmount = $toReturn['invoices'][$key]->paymentAmount + ($this->panelInit->settingsArray['paymentTax']*$toReturn['invoices'][$key]->paymentAmount) /100;

				if($value->paymentStatus == 1){

					$paymentStatus = "PAID";

				}elseif($value->paymentStatus == 2){

					$paymentStatus = "PARTIALLY PAID";

				}else{

					$paymentStatus = "UNPAID";

				}

				$data[] = array($value->paymentTitle,$value->paymentDescription,$value->fullName,$return['currency_symbol']." ".$value->paymentAmount,$return['currency_symbol']." ".$value->paidAmount,$value->paymentDate,$value->dueDate,$paymentStatus);

			}



			\Excel::create('Payments-Sheet', function($excel) use($data) {



			    // Set the title

			    $excel->setTitle('Payments Sheet');



			    // Chain the setters

			    $excel->setCreator('Schoex')->setCompany('SolutionsBricks');



				$excel->sheet('Payments', function($sheet) use($data) {

					$sheet->freezeFirstRow();

					$sheet->fromArray($data, null, 'A1', true,false);

				});



			})->download('xls');



		}elseif ($type == "pdf") {

			$return['currency_symbol'] = $this->panelInit->settingsArray['currency_symbol'];



			$header = array ('Invoice ID','Title','Student','Amount','Paid Amount','Date','Due Date','Status');

			$data = array();



			$toReturn['invoices'] = \DB::table('payments')

						->leftJoin('users', 'users.id', '=', 'payments.paymentStudent')

						->select('payments.id as id',

						'payments.paymentTitle as paymentTitle',

						'payments.paymentDescription as paymentDescription',

						'payments.paymentAmount as paymentAmount',

						'payments.paidAmount as paidAmount',

						'payments.paymentStatus as paymentStatus',

						'payments.paymentDate as paymentDate',

						'payments.dueDate as dueDate',

						'payments.paymentStudent as studentId',

						'users.fullName as fullName');

			$toReturn['totalItems'] = $toReturn['invoices']->count();

			$toReturn['invoices'] = $toReturn['invoices']->orderBy('id','DESC')->limit('100')->get();



			foreach ($toReturn['invoices'] as $key => $value) {

				$value->paymentDate = $this->panelInit->unix_to_date($toReturn['invoices'][$key]->paymentDate);

				$value->dueDate = $this->panelInit->unix_to_date($toReturn['invoices'][$key]->dueDate);

				$value->paymentAmount = $toReturn['invoices'][$key]->paymentAmount + ($this->panelInit->settingsArray['paymentTax']*$toReturn['invoices'][$key]->paymentAmount) /100;

				if($value->paymentStatus == 1){

					$paymentStatus = "PAID";

				}elseif($value->paymentStatus == 2){

					$paymentStatus = "PARTIALLY PAID";

				}else{

					$paymentStatus = "UNPAID";

				}

				$data[] = array($value->paymentTitle,$value->paymentDescription,$value->fullName,$return['currency_symbol']." ".$value->paymentAmount,$return['currency_symbol']." ".$value->paidAmount,$value->paymentDate,$value->dueDate,$paymentStatus);

			}



			$doc_details = array(

								"title" => "Payments",

								"author" => $this->data['panelInit']->settingsArray['siteTitle'],

								"topMarginValue" => 10

								);



			$pdfbuilder = new \PdfBuilder($doc_details);



			$content = "<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">

		        <thead><tr>";

				foreach ($header as $value) {

					$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value."</th>";

				}

			$content .="</tr></thead><tbody>";



			foreach($data as $row)

			{

				$content .= "<tr>";

				foreach($row as $col){

					$content .="<td>".$col."</td>";

				}

				$content .= "</tr>";

			}



	        $content .= "</tbody></table>";



			$pdfbuilder->table($content, array('border' => '0','align'=>'') );

			$pdfbuilder->output('Payments.pdf');



		}

	}



	public static function generateInvoice($user,$case){

		if($user->studentClass == "" || $user->studentClass == "0"){

			return;

		}



		$feeAllocationUser = \fee_allocation::where('allocationType','student')->where('allocationWhen',$case)->where('allocationId',$user->id)->get()->toArray();

		$feeAllocationClass = \fee_allocation::where('allocationType','class')->where('allocationWhen',$case)->where('allocationId',$user->studentClass)->get()->toArray();



		$feeTypesArray = array();

		$feeTypes = \fee_type::get();

		foreach($feeTypes as $type){

			$feeTypesArray[$type->id] = $type->feeTitle;

		}



		if(count($feeAllocationUser) > 0){

			foreach ($feeAllocationUser as $allocatedUser) {



				$paymentDescription = array();

				$paymentAmount = 0;

				$allocationValues = json_decode($allocatedUser->allocationValues,true);

				while (list($key, $value) = each($allocationValues)) {

					if(isset($feeTypesArray[$key])){

						$paymentDescription[] = $feeTypesArray[$key];

						$paymentAmount += $value;

					}

				}



				$payments = new \payments();

				$payments->paymentTitle = $allocatedUser->allocationTitle;

				$payments->paymentDescription = implode(", ",$paymentDescription);

				$payments->paymentStudent = $user->id;

				$payments->paymentAmount = $paymentAmount;

				$payments->paymentStatus = "0";

				$payments->paymentDate = time();

				$payments->paymentUniqid = uniqid();

				$payments->save();



			}

		}



		if(count($feeAllocationClass) > 0){

			foreach ($feeAllocationClass as $allocatedUser) {



				$paymentDescription = array();

				$paymentAmount = 0;

				$allocationValues = json_decode($allocatedUser['allocationValues'],true);

				while (list($key, $value) = each($allocationValues)) {

					if(isset($feeTypesArray[$key])){

						$paymentDescription[] = $feeTypesArray[$key];

						$paymentAmount += $value;

					}

				}



				$payments = new \payments();

				$payments->paymentTitle = $allocatedUser['allocationTitle'];

				$payments->paymentDescription = implode(", ",$paymentDescription);

				$payments->paymentStudent = $user->id;

				$payments->paymentAmount = $paymentAmount;

				$payments->paymentStatus = "0";

				$payments->paymentDate = time();

				$payments->paymentUniqid = uniqid();

				$payments->save();



			}

		}



	}

}
?>