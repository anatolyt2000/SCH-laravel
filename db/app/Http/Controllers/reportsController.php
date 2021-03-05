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
            if(\Input::get('stats') == 'marksheetGenerationPrepare'){
                return $this->marksheetGenerationPrepare();
            }
            if(\Input::get('stats') == 'transpo'){
                return $this->transpo();
            }
            if(\Input::get('stats') == 'enrollmentReport'){
                return $this->enrollmentReport();
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
            if(\Input::get('stats') == 'receipt'){
                return $this->receipt(\Input::get('data'));
            }
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

	public function reports($data){

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
					'users.fullName as fullName');

		if($data['status'] != "All"){
			$payments = $payments->where('paymentStatus',$data['status']);
		}
                
                if ($data['student'] != "All") {
                    $payments = $payments->where('users.id', $data['student']);
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

	public function marksheetGenerationPrepare(){
		$toReturn = array();
		$toReturn['classes'] = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get()->toArray();
		$toReturn['exams'] = \exams_list::where('examAcYear',$this->panelInit->selectAcYear)->get()->toArray();
		return $toReturn;
	}
        
        public function transpo() {
            $toReturn = array();
            $toReturn['transpoList'] = \transportation::all();
            $toReturn['transpoUserCount'] = array();
            $toReturn['transpoUserTotalCount'] = 0;
            
            foreach ($toReturn['transpoList'] as $k => $v) {
                $toReturn['transpoUserTotalCount'] += $toReturn['transpoUserCount'][$k] = \User::where(['role' => 'student', 'transport' => $v->id])->count();
            }
            
            return $toReturn;
        }
        
        public function enrollmentReport() {
            return array(
                'classes' => \classes::select('id', 'className')->get()
            ,   'sections' => \sections::select('id', 'sectionName', 'sectionTitle')->get()
            );
        }
        
        public function enrollment($data) {
            $enrollment = \DB::table('users')
            ->join('academic_year', 'academic_year.id', '=', 'users.studentAcademicYear')
            ->join('country', 'country.id', '=', 'users.country')
            ->select(
                'country.countryTitle',
                'academic_year.yearTitle',
                \DB::raw('COUNT(users.id) AS total'),
                \DB::raw('SUM(if (users.gender = "female", 1, 0)) AS female'),
                \DB::raw('SUM(if (users.gender = "male", 1, 0)) AS male'),
                \DB::raw('SUM(if (users.islam = 1, 1, 0)) AS islam'),
                \DB::raw('SUM(if (users.islam = 0, 1, 0)) AS nonIslam')
            )
            ->where('users.role', 'student')
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
            
            return $enrollment->get();   
        }
        
        public function paymentsReports() {
            return \User::select('id', 'firstName', 'familyName')->where('role', 'student')->get();
        }
        
        public function receiptReport() {
            return array(
                'classes' => \classes::select('id', 'className')->get()
            ,   'sections' => \sections::select('id', 'sectionName', 'sectionTitle')->get()
            );
        }
        
        public function receipt($data) {
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
                'payments.paymentDescription'
            );
            
            if (isset($data['reportType'])) { switch ($data['reportType']) {
                case 2 : $receipt->where('payments.paymentStatus', 1); break;
                case 3 : $receipt->where('payments.paymentStatus', 0); break;
            }}
            
            if (isset($data['fromDate'], $data['toDate'])) {
                $data['fromDate']   = $this->panelInit->date_to_unix($data['fromDate']);
                $data['toDate']     = $this->panelInit->date_to_unix($data['toDate']);
                
                $receipt->where('payments.paymentDate', '>=', $data['fromDate'])
                        ->where('payments.paymentDate', '<=', $data['toDate']);
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
            $summary->select(
                'payments.paymentStatus',
                'academic_year.yearTitle',
                \DB::raw('SUM(payments.paymentAmount) AS amountTotal'),
                'payments.paymentDescription'
            );
            $summary->groupBy('payments.paymentStatus', 'payments.paymentDescription');
            
            return array('receipt' => $receipt->get(), 'summary' => $summary->get());
        }
}
