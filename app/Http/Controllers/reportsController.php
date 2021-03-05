<?php

namespace App\Http\Controllers;



class reportsController extends Controller {



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



		if(!$this->panelInit->hasThePerm('Reports')){

			exit;

		}

	}

	public function report(){

            if($this->data['users']->role != "admin") exit;



            if(\Input::get('stats') == 'usersStats'){

                return $this->usersStats();

            }

            if(\Input::get('stats') == 'stdAttendance'){

                return $this->stdAttendance(\Input::get('data'));

            }

            if(\Input::get('stats') == 'stfAttendance'){

                return $this->stfAttendance(\Input::get('data'));

            }

            if(\Input::get('stats') == 'stdVacation'){

                return $this->stdVacation(\Input::get('data'));

            }

            if(\Input::get('stats') == 'stfVacation'){

                return $this->stfVacation(\Input::get('data'));

            }

            if(\Input::get('stats') == 'payments'){

                return $this->reports(\Input::get('data'));

            }

			if(\Input::get('stats') == 'deletedinvoices'){

                return $this->deletedInvoiceReports(\Input::get('data'));

			}
			
            if(\Input::get('stats') == 'marksheetGenerationPrepare'){

                return $this->marksheetGenerationPrepare();

            }

            if(\Input::get('stats') == 'transpo'){

                return $this->transpo();

            }

            if(\Input::get('stats') == 'enrollmentReport'){

                return $this->enrollmentReport();

			}
			
			if(\Input::get('stats') == 'academicReport'){

                return $this->academicReport();

			}

			if(\Input::get('stats') == 'academicResultReport'){
                   
                return $this->academicResultReport(\Input::get('data'));
                   
			}
            
            if(\Input::get('stats') == 'enrollment'){

                return $this->enrollment(\Input::get('data'));

            }

            if(\Input::get('stats') == 'paymentsReports'){

                return $this->paymentsReports();

            }

            if(\Input::get('stats') == 'receiptReport'){

                return $this->receiptReport();

						}

		      	if(\Input::get('stats') == 'summaryReport'){

                return $this->summaryReport();

            }

            if(\Input::get('stats') == 'receipt'){

                return $this->receipt(\Input::get('data'));

		      	}

						if(\Input::get('stats') == 'summary'){

			                return $this->summary(\Input::get('data'));

						}

						if(\Input::get('stats') == 'history'){

			                // return $this->history(\Input::get('data'));
											return $this->invoice_student(\Input::get('data'));


						}

						if(\Input::get('stats') == 'vat'){

			                return $this->vatReport(\Input::get('data'));

						}

					if(\Input::get('stats') == 'parentreport'){
		                return $this->parentreport();

					}

	}

	public function export($id){

			$data = array(1 => array ('','APR','MAY','JUNE','JULY','AUG','SEP','OCT','NOV','DEC','JAN','FEB','MAR'));

			$return['year'] = \academic_year::where('isDefault','1')->get()->toArray();
			$return['year'] = $return['year'][0]['yearTitle'];
			$return['year'] = explode("-", $return['year']);
			$return['year']=$return['year'][0]-1;


			$return['TF']=\payments::where('paymentStudent',$id)->where('paymentTitle','like','TF%')->get();

			$return['year_TF']=array();
			$month=array();
			$month1=array();

			foreach ($return['TF'] as $key => $value) {
				$return['TF'][$key]->paymentDate = $this->panelInit->unix_to_date($return['TF'][$key]->paymentDate);
				if(strpos($return['TF'][$key]->paymentDate,"".$return['year']."")) {
					$pay_month = explode("/", $return['TF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month[$pay_month-0]=$return['TF'][$key];

				}
				if(strpos($return['TF'][$key]->paymentDate,"".($return['year']+1)."")) {
					$pay_month = explode("/", $return['TF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month1[$pay_month-0]=$return['TF'][$key];

				}
			}

			$return['month1']=$month;
			$return['month2']=$month1;



			$return['BF']=\payments::where('paymentStudent',$id)->where('paymentTitle','like','BF%')->get();
			$return['year_BF']=array();
			$month_BF=array();
			$month1_BF=array();
			foreach ($return['BF'] as $key => $value) {
				$return['BF'][$key]->paymentDate = $this->panelInit->unix_to_date($return['BF'][$key]->paymentDate);
				if(strpos($return['BF'][$key]->paymentDate,"".$return['year']."")) {
					$pay_month = explode("/", $return['TF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month_BF[$pay_month-0]=$return['BF'][$key];
				}
				if(strpos($return['BF'][$key]->paymentDate,"".($return['year']+1)."")) {
					$pay_month = explode("/", $return['BF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month1_BF[$pay_month-0]=$return['BF'][$key];
				}
			}
			$return['month1_BF']=$month_BF;
			$return['month2_BF']=$month1_BF;


			$return['MF']=\payments::where('paymentStudent',$id)->where('paymentTitle','like','MF%')->get();
			$return['year_MF']=array();
			$month_MF=array();
			$month1_MF=array();
			foreach ($return['MF'] as $key => $value) {
				$return['MF'][$key]->paymentDate = $this->panelInit->unix_to_date($return['MF'][$key]->paymentDate);
				if(strpos($return['MF'][$key]->paymentDate,"".$return['year']."")) {
					$pay_month = explode("/", $return['MF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month_MF[$pay_month-0]=$return['MF'][$key];
				}
				if(strpos($return['MF'][$key]->paymentDate,"".($return['year']+1)."")) {
					$pay_month = explode("/", $return['MF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month1_MF[$pay_month-0]=$return['MF'][$key];
				}
			}
			$return['month1_MF']=$month_MF;
			$return['month2_MF']=$month1_MF;

			$return['AF']=\payments::where('paymentStudent',$id)->where('paymentTitle','like','AF%')->get();
			$return['year_AF']=array();
			$month_AF=array();
			$month1_AF=array();
			foreach ($return['AF'] as $key => $value) {
				$return['AF'][$key]->paymentDate = $this->panelInit->unix_to_date($return['AF'][$key]->paymentDate);
				if(strpos($return['AF'][$key]->paymentDate,"".$return['year']."")) {
					$pay_month = explode("/", $return['AF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month_AF[$pay_month-0]=$return['AF'][$key];
				}
				if(strpos($return['AF'][$key]->paymentDate,"".($return['year']+1)."")) {
					$pay_month = explode("/", $return['AF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month1_AF[$pay_month-0]=$return['AF'][$key];
				}
			}
			$return['month1_AF']=$month_AF;
			$return['month2_AF']=$month1_AF;

			$return['LF']=\payments::where('paymentStudent',$id)->where('paymentTitle','like','LF%')->get();
			$return['year_LF']=array();
			$month_LF=array();
			$month1_LF=array();
			foreach ($return['LF'] as $key => $value) {
				$return['LF'][$key]->paymentDate = $this->panelInit->unix_to_date($return['LF'][$key]->paymentDate);
				if(strpos($return['LF'][$key]->paymentDate,"".$return['year']."")) {
					$pay_month = explode("/", $return['LF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month_LF[$pay_month-0]=$return['LF'][$key];
				}
				if(strpos($return['LF'][$key]->paymentDate,"".($return['year']+1)."")) {
					$pay_month = explode("/", $return['LF'][$key]->paymentDate);
					$pay_month = $pay_month[0];
					$month1_LF[$pay_month-0]=$return['LF'][$key];
				}
			}
			$return['month1_LF']=$month_LF;
			$return['month2_LF']=$month1_LF;

			// $data=$return['TF'];
			$tf_data=array();
			for($i=1;$i<13;$i++){
				if($i>4){
					if(isset($return['month1'][''.$i.''])) array_push($tf_data,$return['month1'][''.$i.'']->paymentAmount);
					else array_push($tf_data,"");
				} else {
					if(isset($return['month2'][''.$i.''])) array_push($tf_data,$return['month2'][''.$i.'']->paymentAmount);
					else array_push($tf_data,"");
				}
			}
			$data[] = array('TF',$tf_data[3],$tf_data[4],$tf_data[5],$tf_data[6],$tf_data[7],$tf_data[8],$tf_data[9],$tf_data[10],$tf_data[11],$tf_data[0],$tf_data[1],$tf_data[2]);

			$mf_data=array();
			for($i=1;$i<13;$i++){
				if($i>4){
					if(isset($return['month1_MF'][''.$i.''])) array_push($mf_data,$return['month1_MF'][''.$i.'']->paymentAmount);
					else array_push($mf_data,"");
				} else {
					if(isset($return['month2_MF'][''.$i.''])) array_push($mf_data,$return['month2_MF'][''.$i.'']->paymentAmount);
					else array_push($mf_data,"");
				}
			}
			$data[] = array('MF',$mf_data[3],$mf_data[4],$mf_data[5],$mf_data[6],$mf_data[7],$mf_data[8],$mf_data[9],$mf_data[10],$mf_data[11],$mf_data[0],$mf_data[1],$mf_data[2]);

			$bf_data=array();
			for($i=1;$i<13;$i++){
				if($i>4){
					if(isset($return['month1_BF'][''.$i.''])) array_push($bf_data,$return['month1_BF'][''.$i.'']->paymentAmount);
					else array_push($bf_data,"");
				} else {
					if(isset($return['month2_BF'][''.$i.''])) array_push($bf_data,$return['month2_BF'][''.$i.'']->paymentAmount);
					else array_push($bf_data,"");
				}
			}
			$data[] = array('BF',$bf_data[3],$bf_data[4],$bf_data[5],$bf_data[6],$bf_data[7],$bf_data[8],$bf_data[9],$bf_data[10],$bf_data[11],$bf_data[0],$bf_data[1],$bf_data[2]);

			$af_data=array();
			for($i=1;$i<13;$i++){
				if($i>4){
					if(isset($return['month1_AF'][''.$i.''])) array_push($af_data,$return['month1_AF'][''.$i.'']->paymentAmount);
					else array_push($af_data,"");
				} else {
					if(isset($return['month2_AF'][''.$i.''])) array_push($af_data,$return['month2_AF'][''.$i.'']->paymentAmount);
					else array_push($af_data,"");
				}
			}
			$data[] = array('AF',$af_data[3],$af_data[4],$af_data[5],$af_data[6],$af_data[7],$af_data[8],$af_data[9],$af_data[10],$af_data[11],$af_data[0],$af_data[1],$af_data[2]);

			$lf_data=array();
			for($i=1;$i<13;$i++){
				if($i>4){
					if(isset($return['month1_LF'][''.$i.''])) array_push($lf_data,$return['month1_LF'][''.$i.'']->paymentAmount);
					else array_push($lf_data,"");
				} else {
					if(isset($return['month2_LF'][''.$i.''])) array_push($lf_data,$return['month2_LF'][''.$i.'']->paymentAmount);
					else array_push($lf_data,"");
				}
			}
			$data[] = array('LF',$lf_data[3],$lf_data[4],$lf_data[5],$lf_data[6],$lf_data[7],$lf_data[8],$lf_data[9],$lf_data[10],$lf_data[11],$lf_data[0],$lf_data[1],$lf_data[2]);

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
	}

	public function vatReport($data){
	  if($this->data['users']->role != "admin") exit;
			$return = array();

	        $return['fromDate']=$this->panelInit->date_to_unix($data['fromDate']);
			$return['toDate']=$this->panelInit->date_to_unix($data['toDate']);

// 			$return['totalAmount'] = \payments::where('paymentDate','>=',$return['fromDate'])

// 						->where('paymentDate','<=',$return['toDate'])
// 						->where('paymentStatus','=',1)
// 						->select(
// 								\DB::raw('SUM(paidAmount) AS payTotal')
// 			   		)->get();

			
			
			
			$receipt = \DB::table('payments')

	            ->join('users', 'users.id', '=', 'payments.paymentStudent')
	            
	            ->leftJoin('country', 'users.country', '=', 'country.id')
                -> where('paidTime','>=',$return['fromDate'])
    			->where('paidTime','<=',$return['toDate'])
				->where('paymentStatus','=',1)
	            ->select(
	                'country.countryTitle',

	                'payments.paymentStatus',

	                'payments.paymentAmount',

					'payments.paidTime',

					'payments.paidAmount'
	            );
	            $receipt = $receipt->get();
                $sum=0;$tax=0;$amount=0;
                foreach ($receipt as $key=>$value) {

					$countryTitle = $receipt[$key]-> countryTitle;
        			if($countryTitle == "Saudi Arabia"){
        				$receipt[$key]->paidAmount = $receipt[$key]->paymentAmount;
					}else{
					    $amountTax = ($this->panelInit->settingsArray['paymentTax']* $receipt[$key]->paymentAmount) /100;
					    $receipt[$key]->paidAmount = $receipt[$key]->paymentAmount + $amountTax;
					    $tax=$tax+$amountTax;
					}
					$amount=$amount+$receipt[$key]->paymentAmount;
					$sum=$sum+$receipt[$key]->paidAmount ;
                }
                $return['TotalAmount']=round($sum,2);
               // $return['Amount']=($sum*0.95);
    	       // $return['tax']=($sum*0.05);
    		    $return['Amount']=round($amount,2);
    			$return['tax']=round($tax,2);
			
			return $return;
	}

	public function parentreport(){
			$toReturn = array();

	    $parentname = array();
			$studentname=array();
			$studentid=array();

			$parentname= \User::where('role','parent')->where('activated','1')->select('id','fullname','mobileNo')->get()->toArray();
	    $toReturn['totalItems']=\User::where('role','parent')->where('activated','1')->count();
			$return_array=array();
			foreach($parentname as $arr_parent)
			{
					$array=array();

			   $id=$arr_parent['id'];
				 $parentname=$arr_parent['fullname'];
				 $mobileNo=$arr_parent['mobileNo'];
	       $std = $this->fetch($id);
				 if($std==NULL){break;}
				 $std_name=$std[0]->student;
	 			 $std_id=$std[0]->id;
				 array_push($array,$parentname);
				 array_push($array,$mobileNo);
				 array_push($array,$std_name);
				 array_push($array,$std_id);
				 array_push($return_array,$array);

			}
			$toReturn['val']=$return_array;
			// die;

			// foreach($subject1 as $arr) {
			//
			// 	  $std = $this->fetch($arr['id']);
	    //     if($std==NULL){break;}
			// 	$std_name=$std[0]->student;
			// 	$std_id=$std[0]->id;
			// 	// $std_id=$this->fetch($arr['id'])[0]->id;
			// 	array_push($studentid, $std_id);
			// 	array_push($studentname,$std_name);
			//
			// }
			//
			// $toReturn['studentname']= $studentname;
			// $toReturn['studentid']= $studentid;

			// die;
			return $toReturn;
	}

	function fetch($id){

			$data = \User::where('role','parent')->where('id',$id)->first()->toArray();

			$data['parentOf'] = json_decode($data['parentOf']);

	    return $data['parentOf'];

	}

	public function usersStats(){

	        $toReturn = array();

	        $toReturn['admins'] = array();

	        $toReturn['admins']['activated'] = \User::where('role','admin')->where('activated','1')->count();

	        $toReturn['admins']['inactivated'] = \User::where('role','admin')->where('activated','0')->count();

	        $toReturn['admins']['total'] = $toReturn['admins']['activated'] + $toReturn['admins']['inactivated'];

	        $toReturn['teachers'] = array();

	        $toReturn['teachers']['activated'] = \User::where('role','teacher')->where('activated','1')->count();

	        $toReturn['teachers']['inactivated'] = \User::where('role','teacher')->where('activated','0')->count();

	        $toReturn['teachers']['total'] = $toReturn['teachers']['activated'] + $toReturn['teachers']['inactivated'];

	        $toReturn['students'] = array();

	        $toReturn['students']['activated'] = \User::where('role','student')->where('activated','1')->count();

	        $toReturn['students']['inactivated'] = \User::where('role','student')->where('activated','0')->count();

	        $toReturn['students']['total'] = $toReturn['students']['activated'] + $toReturn['students']['inactivated'];

	        $toReturn['parents'] = array();

	        $toReturn['parents']['activated'] = \User::where('role','parent')->where('activated','1')->count();

	        $toReturn['parents']['inactivated'] = \User::where('role','parent')->where('activated','0')->count();

	        $toReturn['parents']['total'] = $toReturn['parents']['activated'] + $toReturn['parents']['inactivated'];

	        return $toReturn;

		}


	    public function preAttendaceStats(){

	        $toReturn = array();

			$classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get();

			$toReturn['classes'] = array();

			$subjList = array();

			foreach ($classes as $class) {

				$class['classSubjects'] = json_decode($class['classSubjects'],true);

				if(is_array($class['classSubjects'])){

					foreach ($class['classSubjects'] as $subject) {

						$subjList[] = $subject;

					}

				}

				$toReturn['classes'][$class->id] = $class->className ;

			}



			$subjList = array_unique($subjList);

			if($this->data['panelInit']->settingsArray['attendanceModel'] == "subject"){

				$toReturn['subjects'] = array();

				if(count($subjList) > 0){

					$subjects = \subject::whereIN('id',$subjList)->get();

					foreach ($subjects as $subject) {

						$toReturn['subjects'][$subject->id] = $subject->subjectTitle ;

					}

				}

			}



			$toReturn['role'] = $this->data['users']->role;

			$toReturn['attendanceModel'] = $this->data['panelInit']->settingsArray['attendanceModel'];



	        return $toReturn;

	    }



	    public function stdAttendance($data){

	        $sql = "select * from attendance where ";

			$sqlArray = array();

			$toReturn = array();



			$students = array();

			$studentArray = \User::where('role','student');

			if(isset($data['classId']) AND $data['classId'] != "" ){

				$studentArray = $studentArray->where('studentClass',$data['classId']);

			}

			if(isset($data['sectionId']) AND $data['sectionId'] != "" ){

				$studentArray = $studentArray->where('studentSection',$data['sectionId']);

			}

			if($this->data['panelInit']->settingsArray['studentsSort'] != ""){

				$studentArray = $studentArray->orderByRaw($this->data['panelInit']->settingsArray['studentsSort']);

			}

			$studentArray = $studentArray->get();



			$subjectsArray = \subject::get();

			$subjects = array();

			foreach ($subjectsArray as $subject) {

				$subjects[$subject->id] = $subject->subjectTitle ;

			}



			if(isset($data['classId']) AND $data['classId'] != "" ){

				$sqlArray[] = "classId='".$data['classId']."'";

			}

			if($this->data['panelInit']->settingsArray['attendanceModel'] == "subject" AND isset($data['subjectId']) AND $data['subjectId'] != ""){

				$sqlArray[] = "subjectId='".$data['subjectId']."'";

			}

			if(isset($data['status']) AND $data['status'] != "All"){

				$sqlArray[] = "status='".$data['status']."'";

			}



			if(isset($data['attendanceDayFrom']) AND $data['attendanceDayFrom'] != "" AND isset($data['attendanceDayTo']) AND $data['attendanceDayTo'] != ""){

				$data['attendanceDayFrom'] = $this->panelInit->date_to_unix($data['attendanceDayFrom']);

				$data['attendanceDayTo'] = $this->panelInit->date_to_unix($data['attendanceDayTo']);

				$sqlArray[] = "date >= (".$data['attendanceDayFrom'].") AND date <= (".$data['attendanceDayTo'].") ";

			}



			$sql = $sql . implode(" AND ", $sqlArray);

			$attendanceArray = \DB::select( \DB::raw($sql) );

			$attendanceList = array();



			foreach ($attendanceArray as $stAttendance) {

				$attendanceList[$stAttendance->studentId] = $stAttendance;

			}



			$i = 0;

			foreach ($studentArray as $stOne) {

				if(isset($attendanceList[ $stOne->id ])){

					$toReturn[$i] = $attendanceList[ $stOne->id ];

					$toReturn[$i]->studentName = $stOne->fullName;

					if($attendanceList[ $stOne->id ]->subjectId != ""){

						$toReturn[$i]->studentSubject = $subjects[$attendanceList[ $stOne->id ]->subjectId];

					}

					$toReturn[$i]->date = $this->panelInit->unix_to_date($attendanceList[ $stOne->id ]->date);

					$toReturn[$i]->studentRollId = $stOne->studentRollId;

					$i ++;

				}

			}



			if(isset($data['exportType']) AND $data['exportType'] == "excel"){

				$data = array(1 => array ('Date','Roll Id', 'Full Name','Subject','Status'));



				foreach ($toReturn as $value) {

					if($value->status == 0){

						$value->status = $this->panelInit->language['Absent'];

					}elseif ($value->status == 1) {

						$value->status = $this->panelInit->language['Present'];

					}elseif ($value->status == 2) {

						$value->status = $this->panelInit->language['Late'];

					}elseif ($value->status == 3) {

						$value->status = $this->panelInit->language['LateExecuse'];

					}elseif ($value->status == 4) {

						$value->status = $this->panelInit->language['earlyDismissal'];

					}

					$data[] = array ($value->date, (isset($value->studentRollId)?$value->studentRollId:""),(isset($value->studentName)?$value->studentName:""),(isset($value->studentSubject)?$value->studentSubject:""),$value->status);

				}



				\Excel::create('Students-Atendance', function($excel) use($data) {



				    // Set the title

				    $excel->setTitle('Students Atendance Report');



				    // Chain the setters

				    $excel->setCreator('Schoex')->setCompany('SolutionsBricks');



					$excel->sheet('Students Atendance', function($sheet) use($data) {

						$sheet->freezeFirstRow();

						$sheet->fromArray($data, null, 'A1', true,false);

					});



				})->download('xls');

			}



			if(isset($data['exportType']) AND $data['exportType'] == "pdf"){

				$header = array ('Date','Roll Id', 'Full Name','Subject','Status');

				$data = array();

				foreach ($toReturn as $value) {

					if($value->status == 0){

						$value->status = $this->panelInit->language['Absent'];

					}elseif ($value->status == 1) {

						$value->status = $this->panelInit->language['Present'];

					}elseif ($value->status == 2) {

						$value->status = $this->panelInit->language['Late'];

					}elseif ($value->status == 3) {

						$value->status = $this->panelInit->language['LateExecuse'];

					}elseif ($value->status == 4) {

						$value->status = $this->panelInit->language['earlyDismissal'];

					}

					$data[] = array ( $value->date, (isset($value->studentRollId)?$value->studentRollId:""),(isset($value->studentName)?$value->studentName:""),(isset($value->studentSubject)?$value->studentSubject:""),$value->status);

				}



				$doc_details = array(

									"title" => "Attendance",

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

				$pdfbuilder->output('Attendance.pdf');



				exit;

			}



			return $toReturn;

	    }



	    public function stfAttendance($data){

	        $sql = "select * from attendance where ";

			$sqlArray = array();

			$toReturn = array();



			$teachers = array();

			$teachersArray = \User::where('role','teacher');



			if($this->data['panelInit']->settingsArray['teachersSort'] != ""){

				$teachersArray = $teachersArray->orderByRaw($this->data['panelInit']->settingsArray['teachersSort']);

			}



			$teachersArray = $teachersArray->get();



			if(isset($data['status']) AND $data['status'] != "All"){

				$sqlArray[] = "status='".$data['status']."'";

			}



			if(isset($data['attendanceDayFrom']) AND $data['attendanceDayFrom'] != "" AND isset($data['attendanceDayTo']) AND $data['attendanceDayTo'] != ""){

				$data['attendanceDayFrom'] = $this->panelInit->date_to_unix($data['attendanceDayFrom']);

				$data['attendanceDayTo'] = $this->panelInit->date_to_unix($data['attendanceDayTo']);

				$sqlArray[] = "date >= (".$data['attendanceDayFrom'].") AND date <= (".$data['attendanceDayTo'].") ";

			}



	        $sqlArray[] = "classId = '0'";



			$sql = $sql . implode(" AND ", $sqlArray);

			$attendanceArray = \DB::select( \DB::raw($sql) );

			$attendanceList = array();



			foreach ($attendanceArray as $stAttendance) {

				$attendanceList[$stAttendance->studentId] = $stAttendance;

			}



			$i = 0;

			foreach ($teachersArray as $stOne) {

				if(isset($attendanceList[$stOne->id])){

					$toReturn[$i] = $attendanceList[$stOne->id];

					$toReturn[$i]->date = $this->panelInit->unix_to_date($attendanceList[$stOne->id]->date);

					$toReturn[$i]->studentName = $stOne->fullName;

					$i ++;

				}

			}



			if(isset($data['exportType']) AND $data['exportType'] == "excel"){

				$data = array(1 => array ('Date', 'Full Name','Status'));

				foreach ($toReturn as $value) {

					if($value->status == 0){

						$value->status = $this->panelInit->language['Absent'];

					}elseif ($value->status == 1) {

						$value->status = $this->panelInit->language['Present'];

					}elseif ($value->status == 2) {

						$value->status = $this->panelInit->language['Late'];

					}elseif ($value->status == 3) {

						$value->status = $this->panelInit->language['LateExecuse'];

					}

					$data[] = array ( $value->date , $value->studentName,$value->status);

				}



				\Excel::create('Staff-Atendance', function($excel) use($data) {



				    // Set the title

				    $excel->setTitle('Staff Atendance Report');



				    // Chain the setters

				    $excel->setCreator('Schoex')->setCompany('SolutionsBricks');



					$excel->sheet('Staff Atendance', function($sheet) use($data) {

						$sheet->freezeFirstRow();

						$sheet->fromArray($data, null, 'A1', true,false);

					});



				})->download('xls');

			}



			if(isset($data['exportType']) AND $data['exportType'] == "pdf"){

				$header = array ('Date', 'Full Name','Status');

				$data = array();

				foreach ($toReturn as $value) {

					if($value->status == 0){

						$value->status = $this->panelInit->language['Absent'];

					}elseif ($value->status == 1) {

						$value->status = $this->panelInit->language['Present'];

					}elseif ($value->status == 2) {

						$value->status = $this->panelInit->language['Late'];

					}elseif ($value->status == 3) {

						$value->status = $this->panelInit->language['LateExecuse'];

					}

					$data[] = array ( $value->date , $value->studentName,$value->status);

				}



				$doc_details = array(

									"title" => "Attendance",

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

				$pdfbuilder->output('Attendance.pdf');



				exit;

			}



			return $toReturn;

	    }



		public function stdVacation($data){

			$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);

			$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);



			$vacationList = \DB::table('vacation')

						->leftJoin('users', 'users.id', '=', 'vacation.userid')

						->select('vacation.id as id',

						'vacation.userid as userid',

						'vacation.vacDate as vacDate',

						'vacation.acceptedVacation as acceptedVacation',

						'users.fullName as fullName')

						->where('vacation.acYear',$this->panelInit->selectAcYear)

						->where('vacation.role','student')

						->where('vacation.vacDate','>=',$data['fromDate'])

						->where('vacation.vacDate','<=',$data['toDate'])

						->get();



			foreach ($vacationList as $key=>$value) {

				$vacationList[$key]->vacDate = $this->panelInit->unix_to_date($vacationList[$key]->vacDate);

			}



			return $vacationList;

		}



		public function stfVacation($data){

			$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);

			$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);



			$vacationList = \DB::table('vacation')

						->leftJoin('users', 'users.id', '=', 'vacation.userid')

						->select('vacation.id as id',

						'vacation.userid as userid',

						'vacation.vacDate as vacDate',

						'vacation.acceptedVacation as acceptedVacation',

						'users.fullName as fullName')

						->where('vacation.acYear',$this->panelInit->selectAcYear)

						->where('vacation.role','teacher')

						->where('vacation.vacDate','>=',$data['fromDate'])

						->where('vacation.vacDate','<=',$data['toDate'])

						->get();



			foreach ($vacationList as $key=>$value) {

				$vacationList[$key]->vacDate = $this->panelInit->unix_to_date($vacationList[$key]->vacDate);

			}



			return $vacationList;



		}



	public function reports($data){  // payments reports



		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);

		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);


		$payments = \DB::table('payments')

					->leftJoin('users', 'users.id', '=', 'payments.paymentStudent')

					->where('payments.paymentDate','>=',$data['fromDate'])

					->where('payments.paymentDate','<=',$data['toDate'])

					->select('payments.id as id',

					'payments.paymentTitle as paymentTitle',

					'payments.paymentDescription as paymentDescription',

					'payments.paymentAmount as paymentAmount',

					'payments.paidAmount as paidAmount',

					'payments.paymentStatus as paymentStatus',

					'payments.paymentDate as paymentDate',

					'payments.dueDate as dueDate',

					'payments.paymentStudent as studentId',

					'users.gender as gender',

					'users.fullName as fullName');

		if (isset($data['gender'])) { switch ($data['gender']) {

			case 1 : $payments->where('users.gender', 'MALE'); break;

			case 2 : $payments->where('users.gender', 'FEMALE'); break;

		}}

		if($data['status'] != "All"){

			$payments = $payments->where('paymentStatus',$data['status']);

		}



		if ($data['student'] != "All") {

			$payments = $payments->where('users.id', $data['student']);

		}

		if(isset($data['classId'])){
			if ($data['classId'] != "All") {

					$payments = $payments->where('users.studentClass', $data['classId']);

			}
		}

		if(isset($data['sections'])){
			if ($data['sections'] != "All") {

					$payments = $payments->where('users.studentSection', $data['sections']);

			}
		}





		if(isset($data['dueInv']) AND $data['dueInv'] == true){

			$payments = $payments->where('dueDate','<',time())->where('paymentStatus','!=','1');

		}

		$payments = $payments->orderBy('id','DESC')->get();



		foreach ($payments as $key=>$value) {

			$payments[$key]->paymentDate = $this->panelInit->unix_to_date($payments[$key]->paymentDate);

			$payments[$key]->dueDate = $this->panelInit->unix_to_date($payments[$key]->dueDate);

			$payments[$key]->paymentAmount = $payments[$key]->paymentAmount + ($this->panelInit->settingsArray['paymentTax']*$payments[$key]->paymentAmount) /100;

		}



		return $payments;

	}

	public function deletedInvoiceReports($data){  // payments reports



		$data['fromDate'] = $this->panelInit->date_to_unix($data['fromDate']);

		$data['toDate'] = $this->panelInit->date_to_unix($data['toDate']);


		$deletedinvoices = \DB::table('deletedinvoices')

					->leftJoin('users', 'users.id', '=', 'deletedinvoices.paymentStudent')

					->where('deletedinvoices.delDate','>=',$data['fromDate'])

					->where('deletedinvoices.delDate','<=',$data['toDate'])

					->select('deletedinvoices.delid as id',

					'deletedinvoices.paymentTitle as paymentTitle',

					'deletedinvoices.paymentDescription as paymentDescription',

					'deletedinvoices.paymentAmount as paymentAmount',

					'deletedinvoices.paidAmount as paidAmount',

					'deletedinvoices.paymentStatus as paymentStatus',

					'deletedinvoices.paymentDate as paymentDate',

					'deletedinvoices.dueDate as dueDate',

					'deletedinvoices.delDate as delDate',

					'deletedinvoices.paymentStudent as studentId',

					'deletedinvoices.delTime as delTime',

					'deletedinvoices.username as username',

					'users.gender as gender',

					'users.fullName as fullName')
					
					->orderby('deletedinvoices.delDate','DESC');

	

		$deletedinvoices = $deletedinvoices->orderBy('id','DESC')->get();

		foreach ($deletedinvoices as $key=>$value) {

			$deletedinvoices[$key]->paymentDate = $this->panelInit->unix_to_date($deletedinvoices[$key]->paymentDate);

			$deletedinvoices[$key]->dueDate = $this->panelInit->unix_to_date($deletedinvoices[$key]->dueDate);

			$deletedinvoices[$key]->delDate = $this->panelInit->unix_to_date($deletedinvoices[$key]->delDate);

			$deletedinvoices[$key]->paymentAmount = $deletedinvoices[$key]->paymentAmount + ($this->panelInit->settingsArray['paymentTax']*$deletedinvoices[$key]->paymentAmount) /100;

		}

		return $deletedinvoices;

	}



	public function marksheetGenerationPrepare(){

			$toReturn = array();

			$toReturn['classes'] = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get()->toArray();

			$toReturn['exams'] = \exams_list::where('examAcYear',$this->panelInit->selectAcYear)->get()->toArray();

			return $toReturn;

	}



	public function transpo() {

		$toReturn = array();

		$toReturn['transpoUserTotalCount'] = 0;

				$toReturn['transpoUserTotalCount'] = \DB::table('users')
				->join('transportation', 'transportation.id', '=', 'users.transport')
		->where('transportation.transportTitle', 'School BUS')
				->count();

				$toReturn['DriverName'] = \transportation::where('transportTitle','School Bus')->select('transportDriverName')->get();
				$toReturn['DriverMobileNo'] = \transportation::where('transportTitle','School Bus')->select('transportDriverContact')->get();

				$toReturn['transpoList']  = \DB::table('users')

				->join('transportation', 'transportation.id', '=', 'users.transport')

				->join('classes', 'classes.id', '=', 'users.studentClass')

				->join('sections', 'classes.id', '=', 'sections.classId')

				->join('country', 'country.id', '=', 'users.country')

				->select(

						'users.fullName',

						'classes.className',

						'sections.sectionName',

						'country.countryTitle',

			'users.passport',

						'users.mobileNo'

				)
				->where('transportation.transportTitle', 'School BUS')
				->orderBy('users.fullName')
				->get();



		return $toReturn;

	}



    public function enrollmentReport() {

        return array(

            'classes' => \classes::select('id', 'className')->get(),
            'sections' => \sections::select('id', 'sectionName', 'sectionTitle')->get(),
			'activeYear'=>\academic_year::get()

        );

    }

    public function academicReport() {

	    return array(

		   'activeYear'=>\academic_year::get()

	    );

	}
	
	public function academicResultReport($data) {

		
	    $academicResult = \DB::table('users')

        		->join('student_academic_years', 'student_academic_years.studentId' ,'=', 'users.id')

				->join('sections', 'sections.id', '=', 'student_academic_years.sectionId')

				->select(

						'users.studentRollId',

						'users.fullName',

						'sections.sectionTitle'
				)
				->where('student_academic_years.academicYearId', $data['year_Id'])
				->where('users.role', 'student');

		$return = $academicResult->get();

		return $return;
				
    }

  public function enrollment($data) {

   	if (isset($data['status']) AND $data['status'] != '' AND $data['status'] == '1') {

			  $enrollment = \DB::table('users')
				->join('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

				->join('country', 'country.id', '=', 'users.country')

				->select(

						'country.countryTitle',

						'academic_year.yearTitle',

						\DB::raw('COUNT(users.id) AS total'),

						\DB::raw('SUM(if (users.gender = "female", 1, 0)) AS female'),

						\DB::raw('SUM(if (users.gender = "male", 1, 0)) AS male'),

						\DB::raw('SUM(if (users.religion = "1", 1, 0)) AS islam'),

            \DB::raw('SUM(if (users.religion != 1, 1, 0)) AS nonIslam')

				)
				->where('users.status', $data['status'])
				->where('users.role', 'student')
				->where('users.country','>',0)
				->where('users.studentAcademicYear',$data['yearId'])
				->groupBy('academic_year.yearTitle', 'country.id')
				->orderBy('country.countryTitle');

				if (isset($data['fromDate'], $data['toDate'])) {

						$data['fromDate']   = $this->panelInit->date_to_unix($data['fromDate']);

						$data['toDate']     = $this->panelInit->date_to_unix($data['toDate']);



						$keyword1 = $data['fromDate'];

						$keyword2 = $data['toDate'];



						$enrollment = $enrollment->where(function($query) use ($keyword1, $keyword2) {

								$query->where('academic_year.yearTitle', 'LIKE', \DB::raw('CONCAT("%", FROM_UNIXTIME('.$keyword1.',"%Y"), "%")'))

								->orWhere('academic_year.yearTitle', 'LIKE', \DB::raw('CONCAT("%", FROM_UNIXTIME('.$keyword2.',"%Y"), "%")'));

						});

				}

	   }else{


					$year_id=array();

					$year_id = \academic_year::where('isDefault','1')->select('id')->get()->toArray();

            $enrollment = \DB::table('users')

            ->join('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

            ->join('country', 'country.id', '=', 'users.country')

            ->select(

                'country.countryTitle',

                'academic_year.yearTitle',

                \DB::raw('COUNT(users.id) AS total'),

                \DB::raw('SUM(if (users.gender = "female", 1, 0)) AS female'),

                \DB::raw('SUM(if (users.gender = "male", 1, 0)) AS male'),

								\DB::raw('SUM(if (users.religion = "1", 1, 0)) AS islam'),

                \DB::raw('SUM(if (users.religion != 1, 1, 0)) AS nonIslam')

            )
            ->where('users.role', 'student')

						// ->where('users.studentAcademicYear',$year_id[0])

						->where('users.studentAcademicYear',$data['yearId'])

            ->groupBy('academic_year.yearTitle', 'country.id')

            ->orderBy('country.countryTitle');




            if (isset($data['fromDate'], $data['toDate'])) {

                $data['fromDate']   = $this->panelInit->date_to_unix($data['fromDate']);

                $data['toDate']     = $this->panelInit->date_to_unix($data['toDate']);



                $keyword1 = $data['fromDate'];

                $keyword2 = $data['toDate'];



                $enrollment = $enrollment->where(function($query) use ($keyword1, $keyword2) {

                    $query->where('academic_year.yearTitle', 'LIKE', \DB::raw('CONCAT("%", FROM_UNIXTIME('.$keyword1.',"%Y"), "%")'))

                    ->orWhere('academic_year.yearTitle', 'LIKE', \DB::raw('CONCAT("%", FROM_UNIXTIME('.$keyword2.',"%Y"), "%")'));

                });

            }



            if (isset($data['classId']) AND $data['classId'] != '' AND $data['classId'] != '0') {

                $enrollment->where('users.studentClass', $data['classId']);

            }


            if (isset($data['sectionId']) AND $data['sectionId'] != '' AND $data['sectionId'] != '0') {

                $enrollment->where('users.studentSection', $data['sectionId']);

            }



            if (isset($data['status']) AND $data['status'] != '' AND $data['status'] != '0') {

                $enrollment->where('users.status', $data['status']);

            }

       }
						// return;

						$return = $enrollment->get();

						$k = 0;

            foreach ($return as $value) {
            	// code...

							$k = $k + $value->total;
            }

						$return['totalItems']= $k;



            return $return;

  }

	public function test(){

					$enrollment = \DB::table('users')

					->join('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

					->join('country', 'country.id', '=', 'users.country')

					->select(

							'country.countryTitle',

							'academic_year.yearTitle',

							\DB::raw('COUNT(users.id) AS total'),

							\DB::raw('SUM(if (users.gender = "female", 1, 0)) AS female'),

							\DB::raw('SUM(if (users.gender = "male", 1, 0)) AS male'),

							\DB::raw('SUM(if (users.religion = "1", 1, 0)) AS islam')
					)
					->where('users.role', 'student');



					$return = $enrollment->get();


					dump($return);
					die();

  }

  public function paymentsReports() {

					$data['classes'] = $classes = \classes::where('classAcademicYear',1)->get()->toArray();
					$classArray = array();
					$classesIds = array();
					while (list(, $value) = each($classes)) {
						$classesIds[] = $value['id'];
						$classArray[$value['id']] = $value['className'];
					}

					$sectionArray = array();
					if(count($classesIds) > 0){
						$data['sections'] = $sections = \sections::whereIn('classId',$classesIds)->get()->toArray();
						while (list(, $value) = each($sections)) {
							$sectionArray[$value['id']] = $value['sectionName'] . " - ". $value['sectionTitle'];
						}
					}

					$data['studentname']= \User::select('id', 'firstName', 'familyName')->where('role', 'student')->get();
          return $data;

  }

  public function receiptReport() {

            return array(

                'classes' => \classes::select('id', 'className')->get()

            ,   'sections' => \sections::select('id', 'sectionName', 'sectionTitle')->get()

            );

  }

  public function summaryReport() {

            return array(

                'classes' => \classes::select('id', 'className')->get()

            ,   'sections' => \sections::select('id', 'sectionName', 'sectionTitle')->get()

            );

  }

  public function summary($data) {

	            $receipt = \DB::table('payments')

	            ->join('users', 'users.id', '=', 'payments.paymentStudent')

	            ->leftJoin('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

	            ->select(

	                \DB::raw('CONCAT(users.firstName, " ", users.familyName) AS fullName '),

	                'payments.paymentStatus',

	                'academic_year.yearTitle',

	                \DB::raw('FROM_UNIXTIME(paymentDate, "%m/%d/%Y") AS paymentDate'),

	                'payments.paymentTitle',

	                'payments.paymentAmount',

									'payments.paymentDescription',

									'payments.paidTime',

									'payments.paidAmount'

								);

					if (isset($data['fromDate'], $data['toDate'])) {

	                $data['fromDate']   = $this->panelInit->date_to_unix($data['fromDate']);

	                $data['toDate']     = $this->panelInit->date_to_unix($data['toDate']);

	                $receipt->where('payments.paidTime', '>=', $data['fromDate'])

	                        ->where('payments.paidTime', '<=', $data['toDate']);

				}

	            if (isset($data['studentId']) AND $data['studentId'] != '') {

	                $receipt->where('users.studentRollId', "8234");

	            }

	            if (isset($data['studentName']) AND $data['studentName'] != '') {

	                $keyword = $data['studentName'];

	                $receipt = $receipt->where(function($query) use ($keyword) {

	                    $query->where('users.firstName','like','%'.$keyword.'%')

	                          ->orWhere('users.secondName','like','%'.$keyword.'%')

	                          ->orWhere('users.thirdName','like','%'.$keyword.'%')

	                          ->orWhere('users.familyName','like','%'.$keyword.'%');

	                });

	            }


	            if (isset($data['passport']) AND $data['passport'] != '') {

	                $receipt->where('users.passport', $data['passport']);

	            }


	            $summary = clone $receipt;

	            $summary->select(

	                'payments.paymentStatus',

	                'academic_year.yearTitle',

	                \DB::raw('SUM(payments.paidAmount) AS amountTotal'),

	                'payments.paymentDescription'

	            );

							$summary->groupBy('payments.paymentStatus', 'payments.paymentDescription');

							$receipt = $receipt->get();

							foreach ($receipt as $key=>$value) {

								$receipt[$key]->paidTime = $this->panelInit->unix_to_date($receipt[$key]->paidTime);

							}

				            return array('receipt' => $receipt, 'summary' => $summary->get());

		}

	public function receipt($data) {
	    
	   
	            $receipt = \DB::table('payments')

	            ->join('users', 'users.id', '=', 'payments.paymentStudent')
	            
	            ->leftJoin('country', 'users.country', '=', 'country.id')

	            ->leftJoin('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

	            ->select(
	                'country.countryTitle',

	                \DB::raw('CONCAT(users.firstName, " ", users.familyName) AS fullName '),

	                'payments.paymentStatus',

	                'academic_year.yearTitle',

	                \DB::raw('FROM_UNIXTIME(paymentDate, "%m/%d/%Y") AS paymentDate'),

	                'payments.paymentTitle',

	                'payments.paymentAmount',

									'payments.paymentDescription',

									'payments.paidTime',

									'payments.paidAmount'
	            );

	            if (isset($data['reportType'])) {

									switch ($data['reportType']) {

										case 2 : $receipt->where('payments.paymentStatus','>', 0); break;

	                	case 3 : $receipt->where('payments.paymentStatus', 0); break;

									}
							}

	            if (isset($data['fromDate'], $data['toDate'])) {

	                $data['fromDate']   = $this->panelInit->date_to_unix($data['fromDate']);

	                $data['toDate']     = $this->panelInit->date_to_unix($data['toDate']);

	                $receipt->where('payments.paidTime', '>=', $data['fromDate'])

	                        ->where('payments.paidTime', '<=', $data['toDate']);
	            }

	            if (isset($data['studentId']) AND $data['studentId'] != '') {

	                $receipt->where('users.studentRollId', $data['studentId']);

	            }

	            if (isset($data['studentName']) AND $data['studentName'] != '') {

	                $keyword = $data['studentName'];

	                $receipt = $receipt->where(function($query) use ($keyword) {

	                    $query->where('users.firstName','like','%'.$keyword.'%')

	                          ->orWhere('users.secondName','like','%'.$keyword.'%')

	                          ->orWhere('users.thirdName','like','%'.$keyword.'%')

	                          ->orWhere('users.familyName','like','%'.$keyword.'%');

	                });

	            }

	            if (isset($data['classId']) AND $data['classId'] != '' AND $data['classId'] != '0') {

	                $receipt->where('users.studentClass', $data['classId']);

	            }

	            if (isset($data['sectionId']) AND $data['sectionId'] != '' AND $data['sectionId'] != '0') {

	                $receipt->where('users.studentSection', $data['sectionId']);

	            }

	            if (isset($data['passport']) AND $data['passport'] != '') {

	                $receipt->where('users.passport', $data['passport']);

	            }

    
	            $summary = clone $receipt;
	            $tempsummary =  clone $receipt;

	            $summary->select(

	                'payments.paymentStatus',

	                'academic_year.yearTitle',

	                \DB::raw('round(SUM(payments.paidAmount),2) AS amountTotal'),

	                'payments.paymentDescription'

	            );

				$summary->groupBy('payments.paymentStatus', 'payments.paymentDescription');
				$summary = $summary -> get();
				
				
				$rtnSummary=array();
				foreach($summary as $key1=>$value1){
				  $tempsummary =  clone $receipt;
				  $tempsummary -> where('payments.paymentStatus',$summary[$key1]->paymentStatus);
				  $tempsummary -> where('payments.paymentDescription',$summary[$key1]->paymentDescription);
				  $subsum=0;
				  $tempsummary = $tempsummary -> get();
				  foreach($tempsummary as $key2=>$value2){
				       
				        $tempsummary[$key2]->paidTime = $this->panelInit->unix_to_date($tempsummary[$key2]->paidTime);
    				 	$countryTitle = $tempsummary[$key2]-> countryTitle;
    			
    					if($countryTitle == "Saudi Arabia"){
    					    $tempsummary[$key2]->paidAmount = $tempsummary[$key2]->paymentAmount;
    					}else{
    					    $amountTax = ($this->panelInit->settingsArray['paymentTax']* $tempsummary[$key2]->paymentAmount) /100;
    					    $tempsummary[$key2]->paidAmount = $tempsummary[$key2]->paymentAmount + $amountTax;
    					}
    					$subsum = $subsum + $tempsummary[$key2]->paidAmount;
    					$subsum = round($subsum, 2);
			       }
			       $temp=array();
			       array_push($temp, $summary[$key1]->paymentStatus);
			       array_push($temp, $summary[$key1]->yearTitle);
			       array_push($temp, $subsum);
			       array_push($temp, $summary[$key1]->paymentDescription);
			       array_push($rtnSummary, $temp);
				}

				$receipt = $receipt->get();
                
                $sum=0;
                
				foreach ($receipt as $key=>$value) {

					$receipt[$key]->paidTime = $this->panelInit->unix_to_date($receipt[$key]->paidTime);
					$countryTitle = $receipt[$key]-> countryTitle;
				// 	$sum = $sum + $receipt[$key]->paidAmount;
					if($countryTitle == "Saudi Arabia"){
					    $receipt[$key]->paidAmount = $receipt[$key]->paymentAmount;
					}else{
					    $amountTax = ($this->panelInit->settingsArray['paymentTax']* $receipt[$key]->paymentAmount) /100;
					    $receipt[$key]->paidAmount = $receipt[$key]->paymentAmount + $amountTax;
					}
					$sum = $sum + $receipt[$key]->paidAmount;

				}
                
                $sum = round($sum, 2);
	            return array('receipt' => $receipt, 'summary' => $rtnSummary, 'total' => $sum);
	           // return array('receipt' => $receipt, 'summary' => $summary, 'total' => $sum);

	}

  public function history($data) {

          if($this->data['users']->role != "admin") exit;


					$receipt = \DB::table('payments')

					->join('users', 'users.id', '=', 'payments.paymentStudent')

					->leftJoin('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')

					->select(

							'users.id',

							\DB::raw('CONCAT(users.firstName, " ", users.familyName) AS fullName '),

							'payments.paymentStatus',

							'academic_year.yearTitle',

							\DB::raw('FROM_UNIXTIME(paymentDate, "%m/%d/%Y") AS paymentDate'),

							'payments.paymentTitle',

							'payments.paymentAmount',

							'payments.paymentDescription',

							'payments.paidTime',

							'payments.paidAmount'

						);


					if (isset($data['studentId']) AND $data['studentId'] != '') {

							$receipt->where('users.studentRollId', "8234");

					}

					if (isset($data['studentName']) AND $data['studentName'] != '') {

							$keyword = $data['studentName'];

							$receipt = $receipt->where(function($query) use ($keyword) {

									$query->where('users.firstName','like','%'.$keyword.'%')

												->orWhere('users.secondName','like','%'.$keyword.'%')

												->orWhere('users.thirdName','like','%'.$keyword.'%')

												->orWhere('users.familyName','like','%'.$keyword.'%');

							});

					}


					if (isset($data['passport']) AND $data['passport'] != '') {

							$receipt->where('users.passport', $data['passport']);

					}


					$summary = clone $receipt;

					$summary->select(

							'payments.paymentStatus',

							'academic_year.yearTitle',

							\DB::raw('SUM(payments.paidAmount) AS amountTotal'),

							'payments.paymentDescription'

					);

					$summary->groupBy('payments.paymentStatus', 'payments.paymentDescription');

					$receipt = $receipt->get();

					foreach ($receipt as $key=>$value) {

						$receipt[$key]->paidTime = $this->panelInit->unix_to_date($receipt[$key]->paidTime);

					}

					return array('receipt' => $receipt, 'summary' => $summary->get());

	}

	public function invoice_student($data){

	        if($this->data['users']->role != "admin") exit;

			$return = array();
			$return_sub = array();
			$return_all = array();
			$tmp_academic_year = array();
			$from_date="";
			$end_date="";


			$sel_academic_year = \academic_year::where('id',$data['year_Id'])->select('yearTitle')->get()->toArray();
			foreach($sel_academic_year as $key => $value){
				$_academic_year=$sel_academic_year[$key]['yearTitle'];
			}

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

			->join('classes', 'classes.id', '=', 'users.studentClass')

			->join('sections', 'sections.id', '=', 'users.studentSection')

			->select('users.id','users.passport','users.fullName','users.studentRollId','classes.className','sections.sectionName')

			->where('users.role', 'student')

			->groupby('users.id');


			if (isset($data['studentId']) AND $data['studentId'] != '') {

					$receipt->where('users.studentRollId', $data['studentId']);

			}

			if (isset($data['studentName']) AND $data['studentName'] != '') {

					$keyword = $data['studentName'];

					$receipt = $receipt->where(function($query) use ($keyword) {

							$query->where('users.firstName','like','%'.$keyword.'%')

										->orWhere('users.secondName','like','%'.$keyword.'%')

										->orWhere('users.thirdName','like','%'.$keyword.'%')

										->orWhere('users.familyName','like','%'.$keyword.'%');

					});

			}


			if (isset($data['passport']) AND $data['passport'] != '') {

					$receipt->where('users.passport', $data['passport']);

			}
			
			if (isset($data['classId']) AND $data['classId'] != '' AND $data['classId'] != '0') {

					$receipt->where('users.studentClass', $data['classId']);

			}

			if (isset($data['sectionId']) AND $data['sectionId'] != '' AND $data['sectionId'] != '0') {

					$receipt->where('users.studentSection', $data['sectionId']);

			}

			$receipt = $receipt->get();
            $sss=array();
            
			$paymentRows_array=array();
			$all_pay=0;
			

			foreach ($receipt as $key => $id_arry) {
                        $id = $receipt[$key]->id;
						$paymentRows="";
						$paymentRows_array=[];
						$return_sub=[];
						$return=[];
					
						$other=0;

						$get_paymentRow = \payments::where('payments.paidTime','>=',$from_date)
						->where('payments.paidTime','<=',$end_date)
						->where('payments.paymentStudent','=',$receipt[$key]->id)
						->get();
                        foreach($get_paymentRow as $key_paymentRow => $value_paymentRow){

						
							$paymentRows = $get_paymentRow[$key_paymentRow]->paymentRows;
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
						foreach($return as $key_arry => $value){
							$temp=array() ;
							$sub_total=0;
							array_push($temp, $key_arry);
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
						$all_pay=$all_pay+$total;
						array_push($return_arry, $temp);

						$return_sub['monthlyData']=$return_arry;

                        $return_sub['id']=$id ;

						$return_sub['className']=$receipt[$key]->className;
						$return_sub['sectionName']=$receipt[$key]->sectionName;
						$return_sub['studentRollId']=$receipt[$key]->studentRollId;
						$return_sub['fullName']=$receipt[$key]->fullName;
						$return_sub['passport']=$receipt[$key]->passport;

						
						array_push($return_all,$return_sub);
						
			}
            
			$return_all['total_pay']=$all_pay;

			return $return_all;

	}

    public function clone_invoice_student($data){

		if($this->data['users']->role != "admin") exit;

		$return = array();
		$return_sub = array();
		$return_all = array();
		$tmp_academic_year = array();
		$from_date="";
		$end_date="";


		$sel_academic_year = \academic_year::where('id',$data['year_Id'])->select('yearTitle')->get()->toArray();
		foreach($sel_academic_year as $key => $value){
			$_academic_year=$sel_academic_year[$key]['yearTitle'];
		}

		$tmp_academic_year = explode ('-',$_academic_year);
		$from_date='04/01/'.$tmp_academic_year[0];
		$end_date='03/31/'.$tmp_academic_year[1];
		$from_date=$this->panelInit->date_to_unix($from_date);
		$end_date=$this->panelInit->date_to_unix($end_date);
		$return_all['from_academic_year']= $from_date;
		$return_all['end_academic_year']= $end_date;

		 
		$receipt = \DB::table('users')

		->join('classes', 'classes.id', '=', 'users.studentClass')

		->join('sections', 'sections.id', '=', 'users.studentSection')

		->select('users.id','users.passport','users.fullName','users.studentRollId','classes.className','sections.sectionName')

		->where('users.role', 'student')

		->groupby('users.id');


		if (isset($data['studentId']) AND $data['studentId'] != '') {

				$receipt->where('users.studentRollId', $data['studentId']);

		}

		if (isset($data['studentName']) AND $data['studentName'] != '') {

				$keyword = $data['studentName'];

				$receipt = $receipt->where(function($query) use ($keyword) {

						$query->where('users.firstName','like','%'.$keyword.'%')

									->orWhere('users.secondName','like','%'.$keyword.'%')

									->orWhere('users.thirdName','like','%'.$keyword.'%')

									->orWhere('users.familyName','like','%'.$keyword.'%');

				});

		}


		if (isset($data['passport']) AND $data['passport'] != '') {

				$receipt->where('users.passport', $data['passport']);

		}
		
		if (isset($data['classId']) AND $data['classId'] != '' AND $data['classId'] != '0') {

				$receipt->where('users.studentClass', $data['classId']);

		}

		if (isset($data['sectionId']) AND $data['sectionId'] != '' AND $data['sectionId'] != '0') {

				$receipt->where('users.studentSection', $data['sectionId']);

		}

		$receipt = $receipt->get();
		$sss=array();
		
		$paymentRows_array=array();
		$all_pay=0;
		

		foreach ($receipt as $key => $id_arry) {
					$id = $receipt[$key]->id;
					$paymentRows="";
					$paymentRows_array=[];
					$return_sub=[];
				
					
					$tf1=$tf2=$tf3=$tf4=$tf5=$tf6=$tf7=$tf8=$tf9=$tf10=$tf12=$tf11=0;
					$bf1=$bf2=$bf3=$bf4=$bf5=$bf6=$bf7=$bf8=$bf9=$bf10=$bf12=$bf11=0;
					$mf1=$mf2=$mf3=$mf4=$mf5=$mf6=$mf7=$mf8=$mf9=$mf10=$mf12=$mf11=0;
					$of1=$of2=$of3=$of4=$of5=$of6=$of7=$of8=$of9=$of10=$of12=$of11=0;
					$of_adm1=$of_adm2=$of_adm3=$of_adm4=$of_adm5=$of_adm6=$of_adm7=$of_adm8=$of_adm9=$of_adm10=$of_adm12=$of_adm11=0;
					$of_ann1=$of_ann2=$of_ann3=$of_ann4=$of_ann5=$of_ann6=$of_ann7=$of_ann8=$of_ann9=$of_ann10=$of_ann12=$of_ann11=0;
					$of_af1=$of_af2=$of_af3=$of_af4=$of_af5=$of_af6=$of_af7=$of_af8=$of_af9=$of_af10=$of_af12=$of_af11=0;
					$of_pro1=$of_pro2=$of_pro3=$of_pro4=$of_pro5=$of_pro6=$of_pro7=$of_pro8=$of_pro9=$of_pro10=$of_pro12=$of_pro11=0;
					$other=0;


					$get_paymentRow = \payments::where('payments.paidTime','>=',$from_date)
					->where('payments.paidTime','<=',$end_date)
					->where('payments.paymentStudent','=',$receipt[$key]->id)
					->get();
					foreach($get_paymentRow as $key_paymentRow => $value_paymentRow){

					
						$paymentRows = $get_paymentRow[$key_paymentRow]->paymentRows;
						$paymentRows_array=json_decode($paymentRows,true);
						if(is_array($paymentRows_array)){
							foreach ($paymentRows_array as $key1 => $value1) {
								$str_tmp="";
								$str_tmp= $paymentRows_array[$key1]["title"];	  
								
								if($str_tmp=="TF-JAN"){
									$tf1 = $tf1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-FEB"){
									$tf2 = $tf2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-MARCH"){
									$tf3 = $tf3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-APRIL"){
									$tf4 = $tf4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-MAY"){
									$tf5 = $tf5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-JUNE"){
									$tf6 = $tf6 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-JULY"){
									$tf7 = $tf7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-AUG"){
									$tf8 = $tf8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-SEP"){
									$tf9 = $tf9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-OCT"){
									$tf10 = $tf10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-NOV"){
									$tf11=$tf11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="TF-DEC"){
									$tf12=$tf12+$paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="BF-JAN"){
									$bf1 = $bf1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-FEB"){
									$bf2 = $bf2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-MARCH"){
									$bf3 = $bf3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-APRIL"){
									$bf4 = $bf4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-MAY"){
									$bf5 = $bf5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-JUNE"){
									$bf6 = $bf6 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-JULY"){
									$bf7 = $bf7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-AUG"){
									$bf8 = $bf8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-SEP"){
									$bf9 = $bf9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-OCT"){
									$bf10 =$bf10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-NOV"){
									$bf11=$bf11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="BF-DEC"){
									$bf12=$bf12+$paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="MF-JAN"){
									$mf1 = $mf1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-FEB"){
									$mf2 = $mf2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-MARCH"){
									$mf3 = $mf3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-APRIL"){
									$mf4 = $mf4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-MAY"){
									$mf5 = $mf5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-JUNE"){
									$mf6 = $mf6 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-JULY"){
									$mf7 = $mf7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-AUG"){
									$mf8 = $mf8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-SEP"){
									$mf9 = $mf9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-OCT"){
									$mf10 = $mf10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-NOV"){
									$mf11=$mf11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="MF-DEC"){
									$mf12=$mf12+$paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-JAN"){
									$of1 = $of1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-FEB"){
									$of2 = $of2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-MARCH"){
									$of3 = $of3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-APRIL"){
									$of4 = $of4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-MAY"){
									$of5 = $of5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-JUNE"){
									$of6 = $of6 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-JULY"){
									$of7 = $of7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AUG"){
									$of8 = $of8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-SEP"){
									$of9 = $of9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-OCT"){
									$of10 = $of10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-NOV"){
									$of11=$of11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-DEC"){
									$of12=$of12+$paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-ADM-JAN"){
									$of_adm1 = $of_adm1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-FEB"){
									$of_adm2 = $of_adm2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-MARCH"){
									$of_adm3 = $of_adm3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-APRIL"){
									$of_adm4 = $of_adm4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-MAY"){
									$of_adm5 = $of_adm5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-JUNE"){
									$of_adm6 = $of_adm6 + $paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-ADM-JULY"){
									$of_adm7 = $of_adm7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-AUG"){
									$of_adm8 = $of_adm8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-SEP"){
									$of_adm9 = $of_adm9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-OCT"){
									$of_adm10 = $of_adm10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-NOV"){
									$of_adm11=$of_adm11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ADM-DEC"){
									$of_adm12=$of_adm12+$paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-ANN-JAN"){
									$of_ann1 = $of_ann1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-FEB"){
									$of_ann2 = $of_ann2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-MARCH"){
									$of_ann3 = $of_ann3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-APRIL"){
									$of_ann4 = $of_ann4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-MAY"){
									$of_ann5 = $of_ann5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-JUNE"){
									$of_ann6 = $of_ann6 + $paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-ANN-JULY"){
									$of_ann7 = $of_ann7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-AUG"){
									$of_ann8 = $of_ann8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-SEP"){
									$of_ann9 = $of_ann9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-OCT"){
									$of_ann10 = $of_ann10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-NOV"){
									$of_ann11=$of_ann11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-ANN-DEC"){
									$of_ann12=$of_ann12+$paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-AF-JAN"){
									$of_af1 = $of_af1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-FEB"){
									$of_af2 = $of_af2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-MARCH"){
									$of_af3 = $of_af3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-APRIL"){
									$of_af4 = $of_af4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-MAY"){
									$of_af5 = $of_af5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-JUNE"){
									$of_af6 = $of_af6 + $paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-AF-JULY"){
									$of_af7 = $of_af7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-AUG"){
									$of_af8 = $of_af8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-SEP"){
									$of_af9 = $of_af9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-OCT"){
									$of_af10 = $of_af10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-NOV"){
									$of_af11=$of_af11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-AF-DEC"){
									$of_af12=$of_af12+$paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-PRO-JAN"){
									$of_pro1 = $of_pro1 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-FEB"){
									$of_pro2 = $of_pro2 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-MARCH"){
									$of_pro3 = $of_pro3 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-APRIL"){
									$of_pro4 = $of_pro4 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-MAY"){
									$of_pro5 = $of_pro5 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-JUNE"){
									$of_pro6 = $of_pro6 + $paymentRows_array[$key1]["amount"];
									continue;
								}

								if($str_tmp=="OF-PRO-JULY"){
									$of_pro7 = $of_pro7 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-AUG"){
									$of_pro8 = $of_pro8 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-SEP"){
									$of_pro9 = $of_pro9 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-OCT"){
									$of_pro10 = $of_pro10 + $paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-NOV"){
									$of_pro11=$of_pro11+$paymentRows_array[$key1]["amount"];
									continue;
								}
								if($str_tmp=="OF-PRO-DEC"){
									$of_pro12=$of_pro12+$paymentRows_array[$key1]["amount"];
									continue;
								}else{
									$other=$other+$paymentRows_array[$key1]["amount"];
								}
							}	
						}
					}
						  
					if($tf1!=0){
							$return_sub['tfjanuary']=$tf1;
					}else{
						  $return_sub['tfjanuary']="";
					}
					if($tf2!=0){
						$return_sub['tffebruary']=$tf2;
					}else{
						  $return_sub['tffebruary']="";
					}
					if($tf3!=0){
							$return_sub['tfmarch']=$tf3;
					}else{
						  $return_sub['tfmarch']="";
					}
					if($tf4!=0){
						$return_sub['tfapril']=$tf4;
					}else{
						  $return_sub['tfapril']="";
					}
					if($tf5!=0){
							$return_sub['tfmay']=$tf5;
					}else{
						  $return_sub['tfmay']="";
					}
					if($tf6!=0){
						$return_sub['tfjune']=$tf6;
					}else{
						 $return_sub['tfjune']="";
					}
					if($tf7!=0){
							$return_sub['tfjuly']=$tf7;
					}else{
						  $return_sub['tfjuly']="";
					}
					if($tf8!=0){
						$return_sub['tfaugust']=$tf8;
					}else{
						  $return_sub['tfaugust']="";
					}
					if($tf9!=0){
							$return_sub['tfseptember']=$tf9;
					}else{
						  $return_sub['tfseptember']="";
					}
					if($tf10!=0){
						$return_sub['tfoctober']=$tf10;
					}else{
						  $return_sub['tfoctober']="";
					}
					if($tf11!=0){
						  $return_sub['tfnovember']=$tf11;
					}else{
						  $return_sub['tfnovember']="";
					}
					if($tf12!=0){
						  $return_sub['tfdecember']=$tf12;
					}else{
						  $return_sub['tfdecember']="";
					}

					if($bf1!=0){
							$return_sub['bfjanuary']=$bf1;
					}else{
						  $return_sub['bfjanuary']="";
					}
					if($bf2!=0){
						$return_sub['bffebruary']=$bf2;
					}else{
						  $return_sub['bffebruary']="";
					}
					if($bf3!=0){
							$return_sub['bfmarch']=$bf3;
					}else{
						  $return_sub['bfmarch']="";
					}
					if($bf4!=0){
						$return_sub['bfapril']=$bf4;
					}else{
						  $return_sub['bfapril']="";
					}
					if($bf5!=0){
							$return_sub['bfmay']=$bf5;
					}else{
						  $return_sub['bfmay']="";
					}
					if($bf6!=0){
						$return_sub['bfjune']=$bf6;
					}else{
						 $return_sub['bfjune']="";
					}
					if($bf7!=0){
							$return_sub['bfjuly']=$bf7;
					}else{
						  $return_sub['bfjuly']="";
					}
					if($bf8!=0){
						$return_sub['bfaugust']=$bf8;
					}else{
						  $return_sub['bfaugust']="";
					}
					if($bf9!=0){
							$return_sub['bfseptember']=$bf9;
					}else{
						  $return_sub['bfseptember']="";
					}
					if($bf10!=0){
						$return_sub['bfoctober']=$bf10;
					}else{
						  $return_sub['bfoctober']="";
					}
					if($bf11!=0){
						  $return_sub['bfnovember']=$bf11;
					}else{
						  $return_sub['bfnovember']="";
					}
					if($bf12!=0){
						$return_sub['bfdecember']=$bf12;
					}else{
						  $return_sub['bfdecember']="";
					}

					if($mf1!=0){
							$return_sub['mfjanuary']=$mf1;
					}else{
						$return_sub['mfjanuary']="";
					}
					if($mf2!=0){
						$return_sub['mffebruary']=$mf2;
					}else{
						$return_sub['mffebruary']="";
					}
					if($mf3!=0){
							$return_sub['mfmarch']=$mf3;
					}else{
						$return_sub['mfmarch']="";
					}
					if($mf4!=0){
						$return_sub['mfapril']=$mf4;
					}else{
						$return_sub['mfapril']="";
					}
					if($mf5!=0){
							$return_sub['mfmay']=$mf5;
					}else{
						$return_sub['mfmay']="";
					}
					if($mf6!=0){
						$return_sub['mfjune']=$mf6;
					}else{
						$return_sub['mfjune']="";
					}
					if($mf7!=0){
							$return_sub['mfjuly']=$mf7;
					}else{
						$return_sub['mfjuly']="";
					}
					if($mf8!=0){
						$return_sub['mfaugust']=$mf8;
					}else{
						$return_sub['mfaugust']="";
					}
					if($mf9!=0){
							$return_sub['mfseptember']=$mf9;
					}else{
						$return_sub['mfseptember']="";
					}
					if($mf10!=0){
						$return_sub['mfoctober']=$mf10;
					}else{
						$return_sub['mfoctober']="";
					}
					if($mf11!=0){
						$return_sub['mfnovember']=$mf11;
					}else{
						$return_sub['mfnovember']="";
					}
					if($mf12!=0){
						$return_sub['mfdecember']=$mf12;
					}else{
						$return_sub['mfdecember']="";
					}

					if($of1!=0){
						$return_sub['ofjanuary']=$of1;
					}else{
						$return_sub['ofjanuary']="";
					}
					if($of2!=0){
						$return_sub['offebruary']=$of2;
					}else{
						$return_sub['offebruary']="";
					}
					if($of3!=0){
							$return_sub['ofmarch']=$of3;
					}else{
						$return_sub['ofmarch']="";
					}
					if($of4!=0){
						$return_sub['ofapril']=$of4;
					}else{
						$return_sub['ofapril']="";
					}
					if($of5!=0){
							$return_sub['ofmay']=$of5;
					}else{
						$return_sub['ofmay']="";
					}
					if($of6!=0){
						$return_sub['ofjune']=$of6;
					}else{
						$return_sub['ofjune']="";
					}
					if($of7!=0){
							$return_sub['ofjuly']=$of7;
					}else{
						$return_sub['ofjuly']="";
					}
					if($of8!=0){
						$return_sub['ofaugust']=$of8;
					}else{
						$return_sub['ofaugust']="";
					}
					if($of9!=0){
							$return_sub['ofseptember']=$of9;
					}else{
						$return_sub['ofseptember']="";
					}
					if($of10!=0){
						$return_sub['ofoctober']=$of10;
					}else{
						$return_sub['ofoctober']="";
					}
					if($of11!=0){
						$return_sub['ofnovember']=$of11;
					}else{
						$return_sub['ofnovember']="";
					}
					if($of12!=0){
						$return_sub['ofdecember']=$of12;
					}else{
						$return_sub['ofdecember']="";
					}

					if($of_adm1!=0){
						$return_sub['of_admjanuary']=$of_adm1;
					}else{
						$return_sub['of_admjanuary']="";
					}
					if($of_adm2!=0){
						$return_sub['of_admfebruary']=$of_adm2;
					}else{
						$return_sub['of_admfebruary']="";
					}
					if($of_adm3!=0){
							$return_sub['of_admmarch']=$of_adm3;
					}else{
						$return_sub['of_admmarch']="";
					}
					if($of_adm4!=0){
						$return_sub['of_admapril']=$of_adm4;
					}else{
						$return_sub['of_admapril']="";
					}
					if($of_adm5!=0){
							$return_sub['of_admmay']=$of_adm5;
					}else{
						$return_sub['of_admmay']="";
					}
					if($of_adm6!=0){
						$return_sub['of_admjune']=$of_adm6;
					}else{
						$return_sub['of_admjune']="";
					}
					if($of_adm7!=0){
							$return_sub['of_admjuly']=$of_adm7;
					}else{
						$return_sub['of_admjuly']="";
					}
					if($of_adm8!=0){
						$return_sub['of_admaugust']=$of_adm8;
					}else{
						$return_sub['of_admaugust']="";
					}
					if($of_adm9!=0){
							$return_sub['of_admseptember']=$of_adm9;
					}else{
						$return_sub['of_admseptember']="";
					}
					if($of_adm10!=0){
						$return_sub['of_admoctober']=$of_adm10;
					}else{
						$return_sub['of_admoctober']="";
					}
					if($of_adm11!=0){
						$return_sub['of_admnovember']=$of_adm11;
					}else{
						$return_sub['of_admnovember']="";
					}
					if($of_adm12!=0){
						$return_sub['of_admdecember']=$of_adm12;
					}else{
						$return_sub['of_admdecember']="";
					}

					if($of_ann1!=0){
						$return_sub['of_annjanuary']=$of_ann1;
					}else{
						$return_sub['of_annjanuary']="";
					}
					if($of_ann2!=0){
						$return_sub['of_annfebruary']=$of_ann2;
					}else{
						$return_sub['of_annfebruary']="";
					}
					if($of_ann3!=0){
							$return_sub['of_annmarch']=$of_ann3;
					}else{
						$return_sub['of_annmarch']="";
					}
					if($of_ann4!=0){
						$return_sub['of_annapril']=$of_ann4;
					}else{
						$return_sub['of_annapril']="";
					}
					if($of_ann5!=0){
							$return_sub['of_annmay']=$of_ann5;
					}else{
						$return_sub['of_annmay']="";
					}
					if($of_ann6!=0){
						$return_sub['of_annjune']=$of_ann6;
					}else{
						$return_sub['of_annjune']="";
					}
					if($of_ann7!=0){
							$return_sub['of_annjuly']=$of_ann7;
					}else{
						$return_sub['of_annjuly']="";
					}
					if($of_ann8!=0){
						$return_sub['of_annaugust']=$of_ann8;
					}else{
						$return_sub['of_annaugust']="";
					}
					if($of_ann9!=0){
							$return_sub['of_annseptember']=$of_ann9;
					}else{
						$return_sub['of_annseptember']="";
					}
					if($of_ann10!=0){
						$return_sub['of_annoctober']=$of_ann10;
					}else{
						$return_sub['of_annoctober']="";
					}
					if($of_ann11!=0){
						$return_sub['of_annnovember']=$of_ann11;
					}else{
						$return_sub['of_annnovember']="";
					}
					if($of_ann12!=0){
						$return_sub['of_anndecember']=$of_ann12;
					}else{
						$return_sub['of_anndecember']="";
					}

					if($of_af1!=0){
						$return_sub['of_afjanuary']=$of_af1;
					}else{
						$return_sub['of_afjanuary']="";
					}
					if($of_af2!=0){
						$return_sub['of_affebruary']=$of_af2;
					}else{
						$return_sub['of_affebruary']="";
					}
					if($of_af3!=0){
							$return_sub['of_afmarch']=$of_af3;
					}else{
						$return_sub['of_afmarch']="";
					}
					if($of_af4!=0){
						$return_sub['of_afapril']=$of_af4;
					}else{
						$return_sub['of_afapril']="";
					}
					if($of_af5!=0){
							$return_sub['of_afmay']=$of_af5;
					}else{
						$return_sub['of_annmay']="";
					}
					if($of_af6!=0){
						$return_sub['of_afjune']=$of_af6;
					}else{
						$return_sub['of_afjune']="";
					}
					if($of_af7!=0){
							$return_sub['of_afjuly']=$of_af7;
					}else{
						$return_sub['of_afjuly']="";
					}
					if($of_af8!=0){
						$return_sub['of_afaugust']=$of_af8;
					}else{
						$return_sub['of_afaugust']="";
					}
					if($of_af9!=0){
							$return_sub['of_afseptember']=$of_af9;
					}else{
						$return_sub['of_afseptember']="";
					}
					if($of_af10!=0){
						$return_sub['of_afoctober']=$of_af10;
					}else{
						$return_sub['of_afoctober']="";
					}
					if($of_af11!=0){
						$return_sub['of_afnovember']=$of_af11;
					}else{
						$return_sub['of_afnovember']="";
					}
					if($of_af12!=0){
						$return_sub['of_afdecember']=$of_af12;
					}else{
						$return_sub['of_afdecember']="";
					}

					if($of_pro1!=0){
						$return_sub['of_projanuary']=$of_pro1;
					}else{
						$return_sub['of_projanuary']="";
					}
					if($of_pro2!=0){
						$return_sub['of_profebruary']=$of_pro2;
					}else{
						$return_sub['of_profebruary']="";
					}
					if($of_pro3!=0){
							$return_sub['of_promarch']=$of_pro3;
					}else{
						$return_sub['of_promarch']="";
					}
					if($of_pro4!=0){
						$return_sub['of_proapril']=$of_pro4;
					}else{
						$return_sub['of_proapril']="";
					}
					if($of_pro5!=0){
							$return_sub['of_promay']=$of_pro5;
					}else{
						$return_sub['of_promay']="";
					}
					if($of_pro6!=0){
						$return_sub['of_projune']=$of_pro6;
					}else{
						$return_sub['of_projune']="";
					}
					if($of_pro7!=0){
							$return_sub['of_projuly']=$of_pro7;
					}else{
						$return_sub['of_projuly']="";
					}
					if($of_pro8!=0){
						$return_sub['of_proaugust']=$of_pro8;
					}else{
						$return_sub['of_proaugust']="";
					}
					if($of_pro9!=0){
							$return_sub['of_proseptember']=$of_pro9;
					}else{
						$return_sub['of_proseptember']="";
					}
					if($of_pro10!=0){
						$return_sub['of_prooctober']=$of_pro10;
					}else{
						$return_sub['of_prooctober']="";
					}
					if($of_pro11!=0){
						$return_sub['of_pronovember']=$of_pro11;
					}else{
						$return_sub['of_pronovember']="";
					}
					if($of_pro12!=0){
						$return_sub['of_prodecember']=$of_pro12;
					}else{
						$return_sub['of_prodecember']="";
					}

					if($other!=0){
						$return_sub['other']=$other;
					}else{
						$return_sub['other']="";
					}

					  $return_sub['tfsubtotal']=$tf1+$tf2+$tf3+$tf4+$tf5+$tf6+$tf7+$tf8+$tf9+$tf10+$tf11+$tf12;
					$return_sub['bfsubtotal']=$bf1+$bf2+$bf3+$bf4+$bf5+$bf6+$bf7+$bf8+$bf9+$bf10+$bf11+$bf12;
					$return_sub['mfsubtotal']=$mf1+$mf2+$mf3+$mf4+$mf5+$mf6+$mf7+$mf8+$mf9+$mf10+$mf11+$mf12;
					$return_sub['ofsubtotal']=$of1+$of2+$of3+$of4+$of5+$of6+$of7+$of8+$of9+$of10+$of11+$of12;
					$return_sub['of_admsubtotal']=$of_adm1+$of_adm2+$of_adm3+$of_adm4+$of_adm5+$of_adm6+$of_adm7+$of_adm8+$of_adm9+$of_adm10+$of_adm11+$of_adm12;
					$return_sub['of_annsubtotal']=$of_ann1+$of_ann2+$of_ann3+$of_ann4+$of_ann5+$of_ann6+$of_ann7+$of_ann8+$of_ann9+$of_ann10+$of_ann11+$of_ann12;
					$return_sub['of_prosubtotal']=$of_pro1+$of_pro2+$of_pro3+$of_pro4+$of_pro5+$of_pro6+$of_pro7+$of_pro8+$of_pro9+$of_pro10+$of_pro11+$of_pro12;
					$return_sub['of_afsubtotal']=$of_af1+$of_af2+$of_af3+$of_af4+$of_af5+$of_af6+$of_af7+$of_af8+$of_af9+$of_af10+$of_af11+$of_af12;
					$return_sub['other_subtotal']=$other;

					$return_sub['id']=$id ;

					$all_pay=$all_pay+$return_sub['tfsubtotal']+$return_sub['bfsubtotal']+$return_sub['mfsubtotal']+$return_sub['ofsubtotal']+$return_sub['of_admsubtotal']+$return_sub['of_annsubtotal']+$return_sub['of_afsubtotal']+$return_sub['of_prosubtotal']+$return_sub['other_subtotal'];
					// $all_pay=$all_pay+$return_sub['tfsubtotal']+$return_sub['bfsubtotal']+$return_sub['mfsubtotal']+$return_sub['ofsubtotal'];

					$return_sub['className']=$receipt[$key]->className;
					$return_sub['sectionName']=$receipt[$key]->sectionName;
					$return_sub['studentRollId']=$receipt[$key]->studentRollId;
					$return_sub['fullName']=$receipt[$key]->fullName;
					$return_sub['passport']=$receipt[$key]->passport;

					
					array_push($return_all,$return_sub);
					
		}
		
		$return_all['total_pay']=$all_pay;

		return $return_all;

}

}
?>