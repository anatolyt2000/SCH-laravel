<?php
namespace App\Http\Controllers;

class StudentsController extends Controller {

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

		if(!$this->panelInit->hasThePerm('students')){
			exit;
		}
	}

	function waitingApproval(){
		if($this->data['users']->role == "student" || $this->data['users']->role == "parent") exit;

		if($this->data['users']->role == "teacher"){
			$classesList = array();
			$classes = \classes::where('classTeacher','LIKE','%"'.$this->data['users']->id.'"%')->get()->toArray();
			while (list(, $value) = each($classes)) {
				$classesList[] = $value['id'];
			}

			if(count($classesList) > 0){
				$students = \User::where('role','student')->whereIn('studentClass',$classesList)->where('activated','0')->orderByRaw("studentRollId + 0 ASC")->get()->toArray();
			}else{
				$students = array();
			}
		}else{
			$students = \User::where('role','student')->where('activated','0')->orderByRaw("studentRollId + 0 ASC")->get()->toArray();
		}

		$classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->get()->toArray();
		$classArray = array();
		$classesIds = array();
		while (list(, $value) = each($classes)) {
			$classesIds[] = $value['id'];
			$classArray[$value['id']] = $value['className'];
		}

		$sectionArray = array();
		if(count($classesIds) > 0){
			$sections = \sections::whereIn('classId',$classesIds)->get()->toArray();
			while (list(, $value) = each($sections)) {
				$sectionArray[$value['id']] = $value['sectionName'] . " - ". $value['sectionTitle'];
			}
		}

		$toReturn = array();
		while (list(, $student) = each($students)) {
			$toReturn[] = array('id'=>$student['id'],"studentRollId"=>$student['studentRollId'],"fullName"=>$student['fullName'],"username"=>$student['username'],"email"=>$student['email'],"isLeaderBoard"=>$student['isLeaderBoard'],"studentClass"=>isset($classArray[$student['studentClass']]) ? $classArray[$student['studentClass']] : "","studentSection"=>isset($sectionArray[$student['studentSection']]) ? $sectionArray[$student['studentSection']] : "");
		}

		return $toReturn;
	}

	function gradStdList(){
		if($this->data['users']->role == "student" || $this->data['users']->role == "parent" || $this->data['users']->role == "teacher") exit;

		$students = \User::where('role','student')->where('studentClass','-1')->orderByRaw("studentRollId + 0 ASC")->get()->toArray();

		$toReturn = array();
		while (list(, $student) = each($students)) {
			$toReturn[] = array('id'=>$student['id'],"studentRollId"=>$student['studentRollId'],"fullName"=>$student['fullName'],"username"=>$student['username'],"email"=>$student['email'],"isLeaderBoard"=>$student['isLeaderBoard']);
		}

		return $toReturn;
	}

	function approveOne($id){
		if($this->data['users']->role == "student" || $this->data['users']->role == "parent") exit;
		$user = \User::find($id);
		$user->activated = 1;
		$user->save();

		return $this->panelInit->apiOutput(true,$this->panelInit->language['approveStudent'],$this->panelInit->language['stdApproved'],array("user"=>$user->id));
	}

	public function listAll($page = 1)
	{
		if($this->data['users']->role == "student") exit;

		$toReturn = array();
		if($this->data['users']->role == "parent" ){
			$studentId = array();
			$parentOf = json_decode($this->data['users']->parentOf,true);
			if(is_array($parentOf)){
				while (list($key, $value) = each($parentOf)) {
					$studentId[] = $value['id'];
				}
			}
			if(count($studentId) > 0){
				$students = \User::where('role','student')->where('activated','1')->whereIn('id', $studentId);
			}
		}elseif($this->data['users']->role == "teacher" ){
			$classesList = array();
			$classes = \classes::where('classAcademicYear',$this->panelInit->selectAcYear)->where('classTeacher','LIKE','%"'.$this->data['users']->id.'"%')->get()->toArray();
			while (list(, $value) = each($classes)) {
				$classesList[] = $value['id'];
			}

			if(count($classesList) > 0){
				$students = \User::where('role','student')->whereIn('studentClass',$classesList)->where('activated','1');
			}
		}else{
			$students = \User::where('role','student')->where('studentClass','!=','-1')->where('activated','1');
		}


		if(\Input::has('searchInput') AND isset($students)){
			$searchInput = \Input::get('searchInput');
			if(is_array($searchInput)){

				if(isset($searchInput['text']) AND strlen($searchInput['text']) > 0 ){
					$keyword = $searchInput['text'];
					$students = $students->where(function($query) use ($keyword){
                                            $query->where('firstName','like','%'.$keyword.'%')
                                                    ->orWhere('secondName','like','%'.$keyword.'%')
                                                    ->orWhere('thirdName','like','%'.$keyword.'%')
                                                    ->orWhere('familyName','like','%'.$keyword.'%')
                                                    ->orWhere('passport','like','%'.$keyword.'%')
                                                    ->orWhere('studentRollId','like','%'.$keyword.'%');
                                        });
				}

//				if(isset($searchInput['parent']) AND strlen($searchInput['parent']) > 0 ){
				//	$students = $students->where('email','like','%'.$searchInput['email'].'%');
//				}

				if(isset($searchInput['gender']) AND strlen($searchInput['gender']) > 0 AND $searchInput['gender'] != "" ){
					$students = $students->where('gender',$searchInput['gender']);
				}

				if(isset($searchInput['gender']) AND strlen($searchInput['gender']) > 0 AND $searchInput['gender'] != "" ){
					$students = $students->where('gender',$searchInput['gender']);
				}

				if(isset($searchInput['class']) AND $searchInput['class'] != "" ){
					$students = $students->where('studentClass',$searchInput['class']);
				}

				if(isset($searchInput['section']) AND $searchInput['section'] != "" ){
					$students = $students->where('studentSection',$searchInput['section']);
				}

			}

		}

		if(isset($students)){
			$toReturn['totalItems'] = $students->count();
			if(\Input::has('sortBy')){
				$sortBy = array('studentRollId + 0 ASC','studentRollId + 0 DESC','firstName ASC','firstName DESC','username ASC','username DESC');
				if (in_array(\Input::get('sortBy'), $sortBy)) {

					$User = \settings::where('fieldName','studentsSort')->first();
					$User->fieldValue = \Input::get('sortBy');
					$User->save();

					$this->data['panelInit']->settingsArray['studentsSort'] = \Input::get('sortBy');
				}
			}

			if($this->data['panelInit']->settingsArray['studentsSort'] != ""){
				$students = $students->orderByRaw($this->data['panelInit']->settingsArray['studentsSort']);
			}
			$students = $students->take('20')->skip(20* ($page - 1) )->get()->toArray();
		}else{
			$students = array();
		}


		$toReturn['classes'] = $classes = \classes::where('classAcademicYear',1)->get()->toArray();
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

		$toReturn['hostel'] = array();
		$hostel = \hostel::get()->toArray();
		$hostelCat = \hostel_cat::get()->toArray();
		$hostelMail = array();

		foreach ($hostel as $value) {
			$hostelMail[$value['id']] = $value['hostelTitle'];
		}

		foreach ($hostelCat as $value) {
			if(isset($hostelMail[$value['catTypeId']])){
				$toReturn['hostel'][$value['id']] =  $hostelMail[$value['catTypeId']] . " - " . $value['catTitle'];
			}
		}

		$toReturn['userRole'] = $this->data['users']->role;

		$toReturn['students'] = array();
		while (list(, $student) = each($students)) {
            $parent = \User::where('role', 'parent')->where('parentOf', 'LIKE', '%"id":"'.$student['id'].'"%');
            $parentCount = $parent->count();
            $parentInfo = $parent->first();

            if(\Input::has('searchInput') AND isset($students) AND isset($searchInput['parent']) AND strlen($searchInput['parent']) > 0) {
                $parentSearch = $parent->where('firstName', 'LIKE', '%'.$searchInput['parent'].'%')
                                        ->orWhere('familyName', 'LIKE', '%'.$searchInput['parent'].'%');

                if (!$parentSearch->count()) {
                    continue;
                }
            }

            $toReturn['students'][] = array('id'=>$student['id'],"studentRollId"=>$student['studentRollId'],"fullName"=>$student['fullName'],"firstName"=>$student['firstName'],"familyName"=>$student['familyName'],"username"=>$student['username'],"email"=>$student['email'],"isLeaderBoard"=>$student['isLeaderBoard'],"studentClass"=>isset($classArray[$student['studentClass']]) ? $classArray[$student['studentClass']] : "","studentSection"=>isset($sectionArray[$student['studentSection']]) ? $sectionArray[$student['studentSection']] : "", 'parentFullName' => $parentCount ? $parentInfo->firstName.' '.$parentInfo->familyName : '');
		}

        $toReturn['usersStatus'] = \users_status::get()->toArray();
        $toReturn['countries'] = \country::get()->toArray();
        $toReturn['religions'] = \religions::get()->toArray();
		$toReturn['studentRollId'] = $this->createStudentId();


        $toReturn['actived_year'] = \academic_year::where('isDefault','1')->get()->toArray();
        // $reReturn['academicID'] = \User::where('studentRollId',$student['studentRollId'])->left
		return $toReturn;
	}

	public function delete($id){
		if($this->data['users']->role != "admin") exit;
		if ( $postDelete = \User::where('role','student')->where('id', $id)->first() )
        {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true,$this->panelInit->language['delStudent'],$this->panelInit->language['stdDeleted']);
        }else{
            return $this->panelInit->apiOutput(false,$this->panelInit->language['delStudent'],$this->panelInit->language['stdNotExist']);
        }
	}

	public function acYearRemove($student,$id){
		if($this->data['users']->role != "admin") exit;
		if ( $postDelete = \student_academic_years::where('studentId',$student)->where('academicYearId', $id)->first() )
        {
            $postDelete->delete();
            return $this->panelInit->apiOutput(true,$this->panelInit->language['delAcademicYears'],$this->panelInit->language['acYearDelSuc']);
        }else{
            return $this->panelInit->apiOutput(false,$this->panelInit->language['delAcademicYears'],$this->panelInit->language['acYearNotExist']);
        }
	}

	public function export(){
		if($this->data['users']->role != "admin") exit;
		$classArray = array();
		$classes = \classes::get();
		foreach ($classes as $class) {
			$classArray[$class->id] = $class->className;
		}

		$sectionsArray = array();
		$sections = \sections::get();
		foreach ($sections as $section) {
			$sectionsArray[$section->id] = $section->sectionName;
		}

		$data = array(1 => array ('Roll', 'Full Name','Gender','Address','Phone No','Mobile No','birthday','Class','Section', 'Nationality'));
		$student = \User::where('role','student')->orderByRaw("studentRollId + 0 ASC")->get();
		foreach ($student as $value) {
			$birthday = "";
			if($value->birthday != 0){
				$birthday = $this->panelInit->unix_to_date($value->birthday);
			}

            $country = \DB::table('country')->where('id', $value->country)->first();
			$data[] = array ($value->studentRollId, $value->firstName.' '.$value->familyName,$value->gender,$value->address,$value->phoneNo,$value->mobileNo,$birthday,isset($classArray[$value->studentClass]) ? $classArray[$value->studentClass] : "",isset($sectionsArray[$value->studentSection]) ? $sectionsArray[$value->studentSection] : "", $country->countryTitle);
		}

		\Excel::create('Students-Sheet', function($excel) use($data) {

		    // Set the title
		    $excel->setTitle('Students Sheet');

		    // Chain the setters
		    $excel->setCreator('Schoex')->setCompany('SolutionsBricks');

			$excel->sheet('Students', function($sheet) use($data) {
				$sheet->freezeFirstRow();
				$sheet->fromArray($data, null, 'A1', true,false);
			});

		})->download('xls');

	}

	public function exportpdf(){
		if($this->data['users']->role != "admin") exit;
		$classArray = array();
		$classes = \classes::get();
		foreach ($classes as $class) {
			$classArray[$class->id] = $class->className;
		}

		$header = array ('Student Id', 'Full Name','Gender','Address','Mobile No','Class', 'Nationality');
		$data = array();
		$student = \User::where('role','student')->orderByRaw("studentRollId + 0 ASC")->get();
		foreach ($student as $value) {
            $country = \DB::table('country')->where('id', $value->country)->first();
			$data[] = array ($value->studentRollId, $value->firstName.' '.$value->familyName,$value->gender,$value->address,$value->mobileNo, isset($classArray[$value->studentClass]) ? $classArray[$value->studentClass] : "", $country->countryTitle);
		}

		$doc_details = array(
							"title" => "Students List",
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
		$pdfbuilder->output('Students.pdf');
	}

	public function import($type){
		if($this->data['users']->role != "admin") exit;

		if (\Input::hasFile('excelcsv')) {

			$classArray = array();
			$classes = \classes::get();
			foreach ($classes as $class) {
				$classArray[$class->className] = $class->id;
			}

			$sectionsArray = array();
			$sections = \sections::get();
			foreach ($sections as $section) {
				$sectionsArray[$section->classId][$section->id] = $section->sectionName." - ".$section->sectionTitle;
			}

			if ( $_FILES['excelcsv']['tmp_name'] )
			{
				$readExcel = \Excel::load($_FILES['excelcsv']['tmp_name'], function($reader) { })->get();

				$dataImport = array("ready"=>array(),"revise"=>array());
				foreach ($readExcel as $row)
				{
					$importItem = array();
					if(isset($row['roll']) AND $row['roll'] != null){
						$importItem['studentRollId'] = $row['roll'];
					}
					if(isset($row['full_name']) AND $row['full_name'] != null){
						$importItem['fullName'] = $row['full_name'];
					}
					if(isset($row['user_name']) AND $row['user_name'] != null){
						$importItem['username'] = $row['user_name'];
					}else{
						continue;
					}
					if(isset($row['e_mail']) AND $row['e_mail'] != null){
						$importItem['email'] = $row['e_mail'];
					}else{
						continue;
					}
					if(isset($row['gender']) AND $row['gender'] != null){
						$importItem['gender'] = $row['gender'];
					}
					if(isset($row['address']) AND $row['address'] != null){
						$importItem['address'] = $row['address'];
					}
					if(isset($row['phone_no']) AND $row['phone_no'] != null){
						$importItem['phoneNo'] = $row['phone_no'];
					}
					if(isset($row['mobile_no']) AND $row['mobile_no'] != null){
						$importItem['mobileNo'] = $row['mobile_no'];
					}
					if(isset($row['birthday']) AND $row['birthday'] != null){
						if($row['birthday'] == ""){
							$importItem['birthday'] = "";
						}else{
							$importItem['birthday'] = $this->panelInit->date_to_unix($row['birthday']);
						}
					}
					if(isset($row['class']) AND $row['class'] != null){
						$importItem['class'] = $row['class'];
						$importItem['studentClass'] = (isset($classArray[$row['class']]))?$classArray[$row['class']]:'';
					}
					if(isset($row['section']) AND $row['section'] != null){
						$importItem['section'] = $row['section'];
						if($importItem['studentClass'] != ''){
							$sectionDb = \sections::where('classId',$importItem['studentClass'])->where('sectionName',$row['section'])->select('id');
							if($sectionDb->count() > 0){
								$sectionDb = $sectionDb->first();
								$importItem['studentSection'] = $sectionDb->id;
							}else{
								$importItem['studentSection'] = '';
							}
						}else{
							$importItem['studentSection'] = '';
						}
					}
					if(isset($row['password']) AND $row['password'] != null){
						$importItem['password'] = $row['password'];
					}

					if(!isset($importItem['class']) || !isset($importItem['studentClass'])){
						$importItem['error'][] = "class";
					}

					$checkUser = \User::where('username',$importItem['username'])->orWhere('email',$importItem['email']);
					if($checkUser->count() > 0){
						$checkUser = $checkUser->first();
						if($checkUser->username == $importItem['username']){
							$importItem['error'][] = "username";
						}
						if($checkUser->email == $importItem['email']){
							$importItem['error'][] = "email";
						}

						$dataImport['revise'][] = $importItem;
					}else{
						$dataImport['ready'][] = $importItem;
					}
				}

				$toReturn = array();
				$toReturn['dataImport'] = $dataImport;
				$toReturn['sections'] = $sectionsArray;

				return $toReturn;
			}
		}else{
			return json_encode(array("jsTitle"=>$this->panelInit->language['Import'],"jsStatus"=>"0","jsMessage"=>$this->panelInit->language['specifyFileToImport'] ));
			exit;
		}
		exit;
	}

	public function reviewImport(){
		if($this->data['users']->role != "admin") exit;

		$classArray = array();
		$classes = \classes::get();
		foreach ($classes as $class) {
			$classArray[$class->id] = $class->className;
		}

		if(\Input::has('importReview')){
			$importReview = \Input::get('importReview');
			if(!isset($importReview['ready'])){
				$importReview['ready'] = array();
			}
			if(!isset($importReview['revise'])){
				$importReview['revise'] = array();
			}
			$importReview = array_merge($importReview['ready'], $importReview['revise']);

			$dataImport = array("ready"=>array(),"revise"=>array());
			while (list(, $row) = each($importReview)) {
				unset($row['error']);
				if(isset($this->panelInit->settingsArray['emailIsMandatory']) AND $this->panelInit->settingsArray['emailIsMandatory'] == 1){
					$checkUser = \User::where('username',$row['username'])->orWhere('email',$row['email']);
				}else{
					$checkUser = \User::where('username',$row['username']);
					if(isset($row['email']) AND $row['email'] != ""){
						$checkUser = $checkUser->orWhere('email',$row['email']);
					}
				}
				if($checkUser->count() > 0){
					$checkUser = $checkUser->first();
					if($checkUser->username == $row['username']){
						$row['error'][] = "username";
					}
					if($checkUser->email == $row['email']){
						$row['error'][] = "email";
					}
				}

				if($row['studentClass'] == "" OR !isset($classArray[$row['studentClass']])){
					$row['error'][] = "class";
				}

				if(isset($row['error']) AND count($row['error']) > 0){
					$dataImport['revise'][] = $row;
				}else{
					$dataImport['ready'][] = $row;
				}
			}

			if(count($dataImport['revise']) > 0){
				return $this->panelInit->apiOutput(false,$this->panelInit->language['Import'],$this->panelInit->language['reviseImportData'],$dataImport);
			}else{
				while (list(, $value) = each($dataImport['ready'])) {
					$User = new \User();
					if(isset($value['email'])){
						$User->email = $value['email'];
					}
					if(isset($value['username'])){
						$User->username = $value['username'];
					}
					if(isset($value['fullName'])){
						$User->fullName = $value['fullName'];
					}
					if(isset($value['password']) AND $value['password'] != ""){
						$User->password = \Hash::make($value['password']);
					}
					$User->role = "student";
					if(isset($value['studentRollId'])){
						$User->studentRollId = $value['studentRollId'];
					}
					if(isset($value['gender'])){
						$User->gender = $value['gender'];
					}
					if(isset($value['address'])){
						$User->address = $value['address'];
					}
					if(isset($value['phoneNo'])){
						$User->phoneNo = $value['phoneNo'];
					}
					if(isset($value['mobileNo'])){
						$User->mobileNo = $value['mobileNo'];
					}
					if(isset($value['birthday'])){
						$User->birthday = $value['birthday'];
					}
					$User->studentAcademicYear = $this->panelInit->selectAcYear;
					if(isset($value['studentClass'])){
						$User->studentClass = $value['studentClass'];
					}
					if(isset($value['studentSection'])){
						$User->studentSection = $value['studentSection'];
					}
					$User->save();

					$studentAcademicYears = new \student_academic_years();
					$studentAcademicYears->studentId = $User->id;
					$studentAcademicYears->academicYearId = $this->panelInit->selectAcYear;
					$studentAcademicYears->classId = $value['studentClass'];
					if($this->panelInit->settingsArray['enableSections'] == true){
						$studentAcademicYears->sectionId = $value['studentSection'];
					}
					$studentAcademicYears->save();

				}
				return $this->panelInit->apiOutput(true,$this->panelInit->language['Import'],$this->panelInit->language['dataImported']);
			}
		}else{
			return $this->panelInit->apiOutput(true,$this->panelInit->language['Import'],$this->panelInit->language['noDataImport']);
			exit;
		}
		exit;
	}

	public function create(){
		if($this->data['users']->role != "admin") exit;
		if(\User::where('username',trim(\Input::get('username')))->count() > 0){
			return $this->panelInit->apiOutput(false,$this->panelInit->language['addStudent'],$this->panelInit->language['usernameUsed']);
		}
//		if(isset($this->panelInit->settingsArray['emailIsMandatory']) AND $this->panelInit->settingsArray['emailIsMandatory'] == 1){
//			if(\User::where('email',\Input::get('email'))->count() > 0){
//				return $this->panelInit->apiOutput(false,$this->panelInit->language['addStudent'],$this->panelInit->language['mailUsed']);
//			}
//		}
		$User = new \User();
		// $User->email = \Input::get('email');
		$User->username = \Input::get('username');
		$User->fullName = strtoupper(\Input::get('firstName'))." ".strtoupper(\Input::get('secondName'))." ".strtoupper(\Input::get('thirdName'))." ".strtoupper(\Input::get('familyName'));
		$User->firstName = strtoupper(\Input::get('firstName'));
		$User->secondName = strtoupper(\Input::get('secondName'));
		$User->thirdName = strtoupper(\Input::get('thirdName'));
		$User->familyName = strtoupper(\Input::get('familyName'));
		$User->password = \Hash::make(\Input::get('password'));
		$User->role = "student";
		$User->studentRollId = $this->createStudentId();
		$User->passport = \Input::get('passport');
		$User->gender = \Input::get('gender');
		$User->address = \Input::get('address');
		$User->phoneNo = \Input::get('phoneNo');
		$User->mobileNo = \Input::get('mobileNo');
		$User->studentAcademicYear = $this->panelInit->selectAcYear;
		$User->studentClass = \Input::get('studentClass');
		if($this->panelInit->settingsArray['enableSections'] == true){
			$User->studentSection = \Input::get('studentSection');
		}
		$User->transport = \Input::get('transport');
		if(\Input::has('hostel')){
			$User->hostel = \Input::get('hostel');
		}
		if(\Input::get('birthday') != ""){
			$User->birthday = $this->panelInit->date_to_unix(\Input::get('birthday'));
		}
		if(\Input::has('comVia')){
			$User->comVia = json_encode(\Input::get('comVia'));
		}
		$User->isLeaderBoard = "";
        $User->country = \input::get('country');
        $User->religion = \input::get('religion');
		$User->save();

		if (\Input::hasFile('photo')) {
			$fileInstance = \Input::file('photo');
			$newFileName = "profile_".$User->id.".jpg";
			$file = $fileInstance->move('uploads/profile/',$newFileName);

			$User->photo = "profile_".$User->id.".jpg";
			$User->save();
		}

		$studentAcademicYears = new \student_academic_years();
		$studentAcademicYears->studentId = $User->id;
		$studentAcademicYears->academicYearId = $this->panelInit->selectAcYear;
		$studentAcademicYears->classId = \Input::get('studentClass');
		if($this->panelInit->settingsArray['enableSections'] == true){
			$studentAcademicYears->sectionId = \Input::get('studentSection');
		}
		$studentAcademicYears->save();

                $parent = \User::where('role', 'parent')->where('parentOf', 'LIKE', '%"id":"'.$User->id.'"%');

                if ($parent->count()) {
                    $parent = $parent->first();
                    $User->parentFullName = $parent->firstName.' '.$parent->familyName;
                }

		return $this->panelInit->apiOutput(true,$this->panelInit->language['addStudent'],$this->panelInit->language['studentCreatedSuccess'],$User->toArray());
	}

	function fetch($id){
		$data = \User::where('role','student')->where('id',$id)->first()->toArray();
		$data['birthday'] = $this->panelInit->unix_to_date($data['birthday']);

		$data['comVia'] = json_decode($data['comVia'],true);
		if(!is_array($data['comVia'])){
			$data['comVia'] = array();
		}

		$data['academicYear'] = array();
		$academicYear = \academic_year::get();
		foreach ($academicYear as $value) {
			$data['academicYear'][$value->id] = $value->yearTitle;
		}

		$DashboardController = new DashboardController();
		$data['studentAcademicYears'] = array();
		$academicYear = \student_academic_years::where('studentId',$id)->orderBy('id','ASC')->get();
		foreach ($academicYear as $value) {
			$data['studentAcademicYears'][] = array("id"=>$value->academicYearId,"default"=>$value->classId,"defSection"=>$value->sectionId,"classes"=>\classes::where('classAcademicYear',$value->academicYearId)->get()->toArray(),"classSections"=>$DashboardController->classesList($value->academicYearId) );
		}


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
		return $data;
	}

	function leaderboard($id){
		if($this->data['users']->role != "admin") exit;

		$user = \User::where('id',$id)->first();
		$user->isLeaderBoard = \Input::get('isLeaderBoard');
		$user->save();

		$this->panelInit->mobNotifyUser('users',$user->id,$this->panelInit->language['notifyIsLedaerBoard']);

		return $this->panelInit->apiOutput(true,$this->panelInit->language['stdLeaderBoard'],$this->panelInit->language['stdNowLeaderBoard']);
	}

	function leaderboardRemove($id){
		if($this->data['users']->role != "admin") exit;
		if ( $postDelete = \User::where('role','student')->where('id', $id)->where('isLeaderBoard','!=','')->first() )
        {
            \User::where('role','student')->where('id', $id)->update(array('isLeaderBoard' => ''));
            return $this->panelInit->apiOutput(true,$this->panelInit->language['stdLeaderBoard'],$this->panelInit->language['stdLeaderRem']);
        }else{
            return $this->panelInit->apiOutput(false,$this->panelInit->language['stdLeaderBoard'],$this->panelInit->language['stdNotLeader']);
        }
	}

	function edit($id){
		if($this->data['users']->role != "admin") exit;
		if(\User::where('username',trim(\Input::get('username')))->where('id','!=',$id)->count() > 0){
			return $this->panelInit->apiOutput(false,$this->panelInit->language['editStudent'],$this->panelInit->language['usernameUsed']);
		}
//		if(isset($this->panelInit->settingsArray['emailIsMandatory']) AND $this->panelInit->settingsArray['emailIsMandatory'] == 1){
//			if(\User::where('email',\Input::get('email'))->where('id','!=',$id)->count() > 0){
//				return $this->panelInit->apiOutput(false,$this->panelInit->language['addStudent'],$this->panelInit->language['mailUsed']);
//			}
//		}
		$User = \User::find($id);
		// $User->email = \Input::get('email');
		$User->username = \Input::get('username');
		$User->fullName = strtoupper(\Input::get('firstName'))." ".strtoupper(\Input::get('secondName'))." ".strtoupper(\Input::get('thirdName'))." ".strtoupper(\Input::get('familyName'));
		$User->firstName = strtoupper(\Input::get('firstName'));
		$User->secondName = strtoupper(\Input::get('secondName'));
		$User->thirdName = strtoupper(\Input::get('thirdName'));
		$User->familyName = strtoupper(\Input::get('familyName'));
		if(\Input::get('password') != ""){
			$User->password = \Hash::make(\Input::get('password'));
		}
		$User->studentRollId = \Input::get('studentRollId');
		$User->passport = \Input::get('passport');
		$User->gender = \Input::get('gender');
		$User->address = \Input::get('address');
		$User->phoneNo = \Input::get('phoneNo');
		$User->mobileNo = \Input::get('mobileNo');
		$User->transport = \Input::get('transport');
		$User->status = \Input::get('status');
		if(\Input::has('hostel')){
			$User->hostel = \Input::get('hostel');
		}
		if(\Input::get('birthday') != ""){
			$User->birthday = $this->panelInit->date_to_unix(\Input::get('birthday'));
		}

		if (\Input::hasFile('photo')) {
			$fileInstance = \Input::file('photo');
			$newFileName = "profile_".$User->id.".jpg";
			$file = $fileInstance->move('uploads/profile/',$newFileName);

			$User->photo = "profile_".$User->id.".jpg";
		}
		if(\Input::has('comVia')){
			$User->comVia = json_encode(\Input::get('comVia'));
		}
        $User->country = \input::get('country');
        $User->religion = \input::get('religion');
		$User->save();

		if(\Input::has('academicYear')){
			$studentAcademicYears = \Input::get('academicYear');
			if(\Input::has('userSection')){
				$studentSection = \Input::get('userSection');
			}
			$academicYear = \student_academic_years::where('studentId',$id)->get();
			foreach ($academicYear as $value) {
				if(isset($studentAcademicYears[$value->academicYearId])){
					$studentAcademicYearsUpdate = \student_academic_years::where('studentId',$User->id)->where('academicYearId',$value->academicYearId)->first();
					$studentAcademicYearsUpdate->classId = $studentAcademicYears[$value->academicYearId];
					if($this->panelInit->settingsArray['enableSections'] == true && \Input::has('userSection')){
						$studentAcademicYearsUpdate->sectionId = $studentSection[$value->academicYearId];
					}
					$studentAcademicYearsUpdate->save();

					\attendance::where('classId',$value->classId)->where('studentId',$User->id)->update(array('classId' => $studentAcademicYears[$value->academicYearId]));
					\exam_marks::where('classId',$value->classId)->where('studentId',$User->id)->update(array('classId' => $studentAcademicYears[$value->academicYearId]));
				}
				if($value->academicYearId == $User->studentAcademicYear){
					$User->studentClass = $studentAcademicYears[$value->academicYearId];
					if($this->panelInit->settingsArray['enableSections'] == true && \Input::has('userSection')){
						$User->studentSection = $studentSection[$value->academicYearId];
					}
					$User->save();
				}
			}
		}

                $parent = \User::where('role', 'parent')->where('parentOf', 'LIKE', '%"id":"'.$id.'"%');

                if ($parent->count()) {
                    $parent = $parent->first();
                    $User->parentFullName = $parent->firstName.' '.$parent->familyName;
                }

		return $this->panelInit->apiOutput(true,$this->panelInit->language['editStudent'],$this->panelInit->language['studentModified'],$User->toArray());
	}

	function medical($id){
		if($this->data['users']->role != "admin") exit;
		$medicalInfo = array('height'=>'','weight'=>'','rh'=>'','inspol'=>'','vacc'=>'','premed'=>'','prfcli'=>'','disab'=>'','contact'=>'','aller'=>'','medica'=>'','immu'=>'','diet'=>'','frac'=>'','surg'=>'','rema'=>'',);

		$user = \User::where('id',$id)->select('medical')->first()->toArray();
		$user['medical'] = json_decode($user['medical'],true);

		if(is_array($user['medical'])){
			while (list($key, $value) = each($user['medical'])) {
				$medicalInfo[$key] = $value;
			}
		}

		return $medicalInfo;
	}

	function saveMedical(){
		if($this->data['users']->role != "admin") exit;

		$User = \User::find(\Input::get('userId'));
		$User->medical = json_encode(\Input::get('data'));
		$User->save();

		return $this->panelInit->apiOutput(true,"Save medical info","Medical history updated");
	}

	function marksheet($id){
		if($id == 0){
			$id = \Auth::user()->id;
			$studentClass = \Auth::user()->studentClass;
		}else{
			$userDetails = \User::where('id',$id)->select('studentClass')->first();
			$studentClass = $userDetails->studentClass;
		}

		$marks = array();
		$examIds = array();
		$examsList = \exams_list::where('examAcYear',$this->panelInit->selectAcYear)->where('examClasses','LIKE','%'.$studentClass.'%')->get();
		foreach ($examsList as $exam) {
			$marks[$exam->id] = array("title"=>$exam->examTitle,"examId"=>$exam->id,"studentId"=>$id,"examMarksheetColumns"=>json_decode($exam->examMarksheetColumns,true));
			$examIds[] = $exam->id;
		}

		if(count($examIds) == 0){
			return $this->panelInit->apiOutput(false,$this->panelInit->language['Marksheet'],$this->panelInit->language['studentHaveNoMarks']);
			exit;
		}

		$examMarks = \exam_marks::where('studentId',$id)->whereIn('examId',$examIds)->get();
		if(count($examMarks) == 0){
			return $this->panelInit->apiOutput(false,$this->panelInit->language['Marksheet'],$this->panelInit->language['studentHaveNoMarks']);
			exit;
		}
		$subject = \subject::get();
		$gradeLevels = \grade_levels::get();

		$subjectArray = array();
		foreach ($subject as $sub) {
			$subjectArray[$sub->id] = array('subjectTitle'=>$sub->subjectTitle,'passGrade'=>$sub->passGrade,'finalGrade'=>$sub->finalGrade);
		}

		$gradeLevelsArray = array();
		foreach ($gradeLevels as $grade) {
			$gradeLevelsArray[$grade->gradeName] = array('from'=>$grade->gradeFrom,"to"=>$grade->gradeTo,"points"=>$grade->gradePoints);
		}

		foreach ($examMarks as $mark) {
			if(!isset($marks[$mark->examId]['counter'])){
				$marks[$mark->examId]['counter'] = 0;
				$marks[$mark->examId]['points'] = 0;
				$marks[$mark->examId]['totalMarks'] = 0;
			}
			if(!isset($subjectArray[$mark->subjectId])){
				continue;
			}
			$marks[$mark->examId]['counter'] ++;
			$marks[$mark->examId]['data'][$mark->id]['subjectName'] = $subjectArray[$mark->subjectId]['subjectTitle'];
			$marks[$mark->examId]['data'][$mark->id]['subjectId'] = $mark->subjectId;
			$marks[$mark->examId]['data'][$mark->id]['examMark'] = json_decode($mark->examMark,true);
			$marks[$mark->examId]['data'][$mark->id]['markComments'] = $mark->markComments;
			$marks[$mark->examId]['data'][$mark->id]['totalMarks'] = $mark->totalMarks;
			$marks[$mark->examId]['data'][$mark->id]['passGrade'] = $subjectArray[$mark->subjectId]['passGrade'];
			$marks[$mark->examId]['data'][$mark->id]['finalGrade'] = $subjectArray[$mark->subjectId]['finalGrade'];
			if($marks[$mark->examId]['data'][$mark->id]['passGrade'] != ""){
				if(intval($marks[$mark->examId]['data'][$mark->id]['totalMarks']) >= intval($marks[$mark->examId]['data'][$mark->id]['passGrade'])){
					$marks[$mark->examId]['data'][$mark->id]['examState'] = "Pass";
				}else{
					$marks[$mark->examId]['data'][$mark->id]['examState'] = "Failed";
				}
			}

			reset($gradeLevelsArray);
			while (list($key, $value) = each($gradeLevelsArray)) {
				if($mark->totalMarks >= $value['from'] AND $mark->totalMarks <= $value['to']){
					$marks[$mark->examId]['points'] += $value['points'];
					$marks[$mark->examId]['data'][$mark->id]['grade'] = $key;
					$marks[$mark->examId]['totalMarks'] += $mark->totalMarks;
					break;
				}
			}
		}

		while (list($key, $value) = each($marks)) {
			if(isset($value['points']) AND $value['counter']){
				$marks[$key]['pointsAvg'] = $value['points'] / $value['counter'];
			}
		}

		return $marks;
		exit;
	}

	function marksheetPDF($studentId,$exam){
		if(\Auth::user()->role == "student"){
			$studentId = \Auth::user()->id;
		}
		$student = \User::where('id',$studentId)->first();
		$examsList = \exams_list::where('id',$exam)->first()->toArray();
		$examsList['examMarksheetColumns'] = json_decode($examsList['examMarksheetColumns'],true);
		$studentMarks = $this->marksheet($studentId);

		if(!isset($studentMarks[$exam]['data'])){
			echo $this->panelInit->language['noMarksheetAv'];
		}

		$doc_details = array(
							"title" => $student->fullName ." Marksheet",
							"author" => $this->data['panelInit']->settingsArray['siteTitle'],
							"topMarginValue" => 10
							);

		$pdfbuilder = new \PdfBuilder($doc_details);

	//	$pdfbuilder->space(10);

		if(file_exists('uploads/profile/profile_'.$studentId.'.jpg')){
			$userAssetImage = 'uploads/profile/profile_'.$studentId.'.jpg';
		}else{
			$userAssetImage = 'uploads/profile/user.png';
		}

		$content = "
		<table cellspacing=\"0\" cellpadding=\"4\" border=\"0\">
			<tr>
				<td width=\"15%\"><img src=\"".\URL::asset('assets/images/logo-light.png')."\"></td>
				<td width=\"70%\" style=\"vertical-align: middle\" ><br/><br/><br/>".$this->data['panelInit']->settingsArray['siteTitle']."<br/>".$student->fullName ." Marksheet"."</td>
				<td width=\"15%\" style=\"vertical-align: right;horizontal-aligh:right;\" ><img width=\"75px\" height=\"75px\" src=\"".\URL::asset($userAssetImage)."\"></td>
			</tr>
		</table>

		<br/><br/>

		<table cellspacing=\"5\" cellpadding=\"4\" border=\"0\">
		        <tr>
		            <td style='width: 50%;text-align: left;'>

						<table cellspacing=\"5\" cellpadding=\"4\" border=\"0\">
					        <tr>
					            <td style=\"width:30%; \">School</td>
					            <td style=\"width:70%; \">".$this->data['panelInit']->settingsArray['siteTitle']."</td>
					        </tr>
					        <tr>
					            <td style=\"width:30%; \">".$this->panelInit->language['Marksheet']." </td>
					            <td style=\"width:70%\">".$this->data['panelInit']->settingsArray['address']."<br>".$this->data['panelInit']->settingsArray['address2']."
					            </td>
					        </tr>
					        <tr>
					            <td style=\"width:30%;\">".$this->panelInit->language['email']." </td>
					            <td style=\"width:70%\">".$this->data['panelInit']->settingsArray['systemEmail']."</td>
					        </tr>
					        <tr>
					            <td style=\"width:30%;\">".$this->panelInit->language['phoneNo']." </td>
					            <td style=\"width:70%\">".$this->data['panelInit']->settingsArray['phoneNo']."</td>
					        </tr>
					    </table>

		            </td>
		            <td style='width: 50%; color: #444444;text-align: left;'>


						<table cellspacing=\"5\" cellpadding=\"4\" border=\"0\">
							<tr>
								<td style=\"width:30%;\">".$this->panelInit->language['student']." </td>
								<td style=\"width:70%\">".$student->fullName."</td>
							</tr>
							<tr>
								<td style=\"width:30%;\">".$this->panelInit->language['Address']." </td>
								<td style=\"width:70%\">".$student->address."</td>
							</tr>
							<tr>
								<td style=\"width:30%;\">".$this->panelInit->language['email']." </td>
								<td style=\"width:70%\">".$student->email."</td>
							</tr>
							<tr>
								<td style=\"width:30%;\">".$this->panelInit->language['phoneNo']." </td>
								<td style=\"width:70%\">".$student->phoneNo." - ".$student->mobileNo."</td>
							</tr>
						</table>


					</td>
		        </tr>
		    </table>

			<br/><br/><br/>
			<table cellspacing='0' style='padding: 1px; width: 100%; font-size: 11pt; '>
	            <tr>
	                <th style='width: 100%; text-align: center; '> <b>".$examsList['examTitle']."</b> </th>
	            </tr>
			</table>
			<br/><br/>

            <table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
                <tbody><tr>
                    <th style='width:15%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Subject']."</th>
					";
					if(isset($examsList['examMarksheetColumns'] ) AND is_array($examsList['examMarksheetColumns'] )){
						foreach ($examsList['examMarksheetColumns'] as $value) {
							$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value['title']."</th>";
						}
					}

					$content .="<th style='width:7%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['mark']."</th>
                    <th style='width:7%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Grade']."</th>
                    <th style='width:15%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['passGrade']."</th>
                    <th style='width:15%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['finalGrade']."</th>
                    <th style='width:10%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Status']."</th>
                    <th style='width:12%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Comments']."</th>
                </tr>";

				foreach ($studentMarks[$exam]['data'] as $value) {
	                $content .= "<tr>
	                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['subjectName']."</td>";

						if(isset($examsList['examMarksheetColumns']) AND is_array($examsList['examMarksheetColumns'])){
							foreach ($examsList['examMarksheetColumns'] as $value_) {
								$content .="<td style='width:15%;border: solid 1px #000000; padding:2px;'>";
								if(isset($value['examMark'][$value_['id']])){
									$content .= $value['examMark'][$value_['id']];
								}
								$content .="</td>";
							}
						}

	                    $content .= "<td style='border: solid 1px #000000;padding:2px;'>".@$value['totalMarks']."</td>
	                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['grade']."</td>
	                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['passGrade']."</td>
	                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['finalGrade']."</td>
	                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['examState']."</td>
	                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['markComments']."</td>
	                </tr>";
				}
            $content .= "</tbody></table>

			<br/><br/>
			<table cellspacing='0' style='padding: 1px; width: 100%; font-size: 10pt; '>
	            <tr>
	                <th style='width: 100%; text-align: center; '> ".$this->panelInit->language['examMarks']." : ".$studentMarks[$exam]['totalMarks']." - ".$this->panelInit->language['AveragePoints']." : ".$studentMarks[$exam]['pointsAvg']." </th>
	            </tr>
			</table>
			<br/><br/>";



		$pdfbuilder->table($content, array('border' => '0','align'=>'') );
		$pdfbuilder->output('Markshhet - '.$student->fullName.'.pdf');

		exit;
	}

	function marksheetBulkPDF(){
		$users = \User::where('studentClass',\Input::get('classId'))->orderByRaw("studentRollId + 0 ASC")->get();
		$examsList = \exams_list::where('id',\Input::get('examId'))->first()->toArray();
		$examsList['examMarksheetColumns'] = json_decode($examsList['examMarksheetColumns'],true);

		$doc_details = array(
							"title" => "Marksheet",
							"author" => $this->data['panelInit']->settingsArray['siteTitle'],
							"topMarginValue" => 10
							);

		$pdfbuilder = new \PdfBuilder($doc_details);

		$content = "";
		foreach ($users as $student) {
			$studentMarks = $this->marksheet($student->id);

			$content = "
			<table cellspacing=\"0\" cellpadding=\"4\" border=\"0\">
				<tr>
					<td width=\"100px\"><img src=\"".\URL::asset('assets/images/logo-light.png')."\"></td>
					<td style=\"vertical-align: middle\" ><br/><br/><br/>".$this->data['panelInit']->settingsArray['siteTitle']."<br/>".$student->fullName ." Marksheet"."</td>
				</tr>
			</table>

			<br/><br/>

			<table cellspacing=\"5\" cellpadding=\"4\" border=\"0\">
			        <tr>
			            <td style='width: 50%;text-align: left;'>

							<table cellspacing=\"5\" cellpadding=\"4\" border=\"0\">
						        <tr>
						            <td style=\"width:30%; \">School</td>
						            <td style=\"width:70%; \">".$this->data['panelInit']->settingsArray['siteTitle']."</td>
						        </tr>
						        <tr>
						            <td style=\"width:30%; \">".$this->panelInit->language['Marksheet']." :</td>
						            <td style=\"width:70%\">".$this->data['panelInit']->settingsArray['address']."<br>".$this->data['panelInit']->settingsArray['address2']."
						            </td>
						        </tr>
						        <tr>
						            <td style=\"width:30%;\">".$this->panelInit->language['email']." :</td>
						            <td style=\"width:70%\">".$this->data['panelInit']->settingsArray['systemEmail']."</td>
						        </tr>
						        <tr>
						            <td style=\"width:30%;\">".$this->panelInit->language['phoneNo']." :</td>
						            <td style=\"width:70%\">".$this->data['panelInit']->settingsArray['phoneNo']."</td>
						        </tr>
						    </table>

			            </td>
			            <td style='width: 50%; color: #444444;text-align: left;'>


							<table cellspacing=\"5\" cellpadding=\"4\" border=\"0\">
								<tr>
									<td style=\"width:30%;\">".$this->panelInit->language['student']." :</td>
									<td style=\"width:70%\">".$student->fullName."</td>
								</tr>
								<tr>
									<td style=\"width:30%;\">".$this->panelInit->language['Address']." :</td>
									<td style=\"width:70%\">".$student->address."</td>
								</tr>
								<tr>
									<td style=\"width:30%;\">".$this->panelInit->language['email']." :</td>
									<td style=\"width:70%\">".$student->email."</td>
								</tr>
								<tr>
									<td style=\"width:30%;\">".$this->panelInit->language['phoneNo']." :</td>
									<td style=\"width:70%\">".$student->phoneNo." - ".$student->mobileNo."</td>
								</tr>
							</table>


						</td>
			        </tr>
			    </table>

				<br/><br/><br/>
				<table cellspacing='0' style='padding: 1px; width: 100%; font-size: 11pt; '>
		            <tr>
		                <th style='width: 100%; text-align: center; '> <b>".$examsList['examTitle']."</b> </th>
		            </tr>
				</table>
				<br/><br/>";

				if(isset($studentMarks[\Input::get('examId')]['data'] )){

	            	$content .="<table cellspacing=\"0\" cellpadding=\"4\" border=\"1\">
	                <tbody><tr>
	                    <th style='width:15%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Subject']."</th>
						";
						if(isset($examsList['examMarksheetColumns']) AND is_array($examsList['examMarksheetColumns']) ){
							foreach ($examsList['examMarksheetColumns'] as $value) {
								$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>".$value['title']."</th>";
							}
						}
						$content .="<th style='width:7%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['mark']."</th>
	                    <th style='width:7%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Grade']."</th>
	                    <th style='width:15%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['passGrade']."</th>
	                    <th style='width:15%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['finalGrade']."</th>
	                    <th style='width:10%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Status']."</th>
	                    <th style='width:12%;border: solid 1px #000000; padding:2px;'>".$this->panelInit->language['Comments']."</th>
	                </tr>";

					foreach ($studentMarks[\Input::get('examId')]['data'] as $value) {
		                $content .= "<tr>
		                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['subjectName']."</td>";

							if(isset($examsList['examMarksheetColumns']) AND is_array($examsList['examMarksheetColumns'])){
								foreach ($examsList['examMarksheetColumns'] as $value_) {
									$content .="<th style='width:15%;border: solid 1px #000000; padding:2px;'>";
									if(isset($value['examMark'][$value_['id']])){
										$content .= $value['examMark'][$value_['id']];
									}
									$content .="</th>";
								}
							}

		                    $content .= "<td style='border: solid 1px #000000;padding:2px;'>".@$value['totalMarks']."</td>
		                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['grade']."</td>
		                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['passGrade']."</td>
		                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['finalGrade']."</td>
		                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['examState']."</td>
		                    <td style='border: solid 1px #000000;padding:2px;'>".@$value['markComments']."</td>
		                </tr>";
					}
	            $content .= "</tbody></table>

				<br/><br/>
				<table cellspacing='0' style='padding: 1px; width: 100%; font-size: 10pt; '>
		            <tr>
		                <th style='width: 100%; text-align: center; '> ".$this->panelInit->language['examMarks']." : ".$studentMarks[\Input::get('examId')]['totalMarks']." - ".$this->panelInit->language['AveragePoints']." : ".$studentMarks[\Input::get('examId')]['pointsAvg']." </th>
		            </tr>
				</table>
				<br/><br/>";
			}

			$pdfbuilder->table($content, array('border' => '0','align'=>'') );
			$pdfbuilder->addPage();
		}

		$pdfbuilder->output('Markshhet.pdf');

		exit;
	}

	function attendance($id){
		$toReturn = array();
		$toReturn['attendanceModel'] = $this->data['panelInit']->settingsArray['attendanceModel'];
		$toReturn['attendance'] = \attendance::where('studentId',$id)->get()->toArray();

		while (list($key, $value) = each($toReturn['attendance'])) {
			$toReturn['attendance'][$key]['date'] = $this->panelInit->unix_to_date($toReturn['attendance'][$key]['date']);
		}

		if($this->data['panelInit']->settingsArray['attendanceModel'] == "subject"){
			$subjects = \subject::get();
			$toReturn['subjects'] = array();
			foreach ($subjects as $subject) {
				$toReturn['subjects'][$subject->id] = $subject->subjectTitle ;
			}
		}
		return $toReturn;
	}

	function profile($id){
		$data = \User::where('role','student')->where('id',$id);

		if($data->count() > 0){
			$data = $data->first()->toArray();
			$data['birthday'] = $this->panelInit->unix_to_date($data['birthday']);

			if($data['studentClass'] != "" AND $data['studentClass'] != "0"){
				$class = \classes::where('id',$data['studentClass'])->first();
			}

			if($data['studentSection'] != "" AND $data['studentSection'] != "0"){
				$section = \sections::where('id',$data['studentSection'])->first();
			}

			$parents = \User::where('parentOf','like','%"'.$id.'"%')->orWhere('parentOf','like','%:'.$id.'}%')->get();

                        if ($data['transport'] != "" AND $data['transport'] != "0") {
                            $tranportation = \transportation::where('id', $data['transport'])->first();
                        }

                        $usersStatus = \users_status::where('id', $data['status'])->first();

            if ($data['studentAcademicYear'] != "" AND $data['studentAcademicYear'] != "0") {
                $academic_year = \academic_year::where('id', $data['studentAcademicYear'])->first();
								$prev_academic_year = \academic_year::where('isDefault', '1')->first();
								$aa=$prev_academic_year->id-1;
								if($aa!=1){
								   $prev_academic_year = \academic_year::where('id', $aa)->first();
								}else{
									$prev_academic_year=$academic_year;
								}

            }

            $total_paid = \payments::where('paymentStudent', $data['id'])->sum('paidAmount');
            $total_unpaid = \payments::where('paymentStudent', $data['id'])->sum('paymentAmount');

			$return = array();
			$return['title'] = $data['fullName']." ".$this->panelInit->language['Profile'];

			$return['content'] = "<div class='text-center'>";

			$return['content'] .= "<img alt='".$data['fullName']."' class='user-image img-circle' style='width:70px; height:70px;' src='index.php/dashboard/profileImage/".$data['id']."'>";

			$return['content'] .= "</div>";

			$return['content'] .= "<h4>".$this->panelInit->language['studentInfo']."</h4>";

			$return['content'] .= "<table class='table table-bordered'><tbody>
	                          <tr>
	                              <td>".$this->panelInit->language['FullName']."</td>
	                              <td>".$data['firstName'].($data['secondName'] ? " ".$data['secondName'] : "").($data['thirdName'] ? " ".$data['thirdName'] : "")." ".$data['familyName']."</td>
	                          </tr>
	                          <tr>
	                              <td>Student Id</td>";
														if($usersStatus->statusTitle=="Active"){
																$return['content'] .= "<td>".$prev_academic_year->yearTitle."-".$data['id']."</td>";
														}else{
																$return['content'] .= "<td>".$data['id']."</td>";
														}

	                          $return['content'] .= "</tr>";
	                          if(isset($class)){
		                          $return['content'] .= "<tr>
		                              <td>".$this->panelInit->language['class']."</td>
		                              <td>".$class->className."</td>
		                          </tr>";
		                        }
								if(isset($section)){
	  	                          $return['content'] .= "<tr>
	  	                              <td>Section</td>
	  	                              <td>".$section->sectionName." - ".$section->sectionTitle."</td>
	  	                          </tr>";
	  	                        }

                               if (isset($academic_year)) {
                                   $return['content'] .= "<tr>
                                       <td>Academic Year</td>
                                       <td>".$prev_academic_year->yearTitle."</td>
                                    </tr>";
                               }

	                          $return['content'] .= "<!--<tr>
	                              <td>".$this->panelInit->language['username']."</td>
	                              <td>".$data['username']."</td>
	                          </tr>
	                          <tr>
	                              <td>".$this->panelInit->language['email']."</td>
	                              <td>".$data['email']."</td>
	                          </tr>-->
	                          <tr>
	                              <td>".$this->panelInit->language['Birthday']."</td>
	                              <td>".$data['birthday']."</td>
	                          </tr>
	                          <tr>
	                              <td>".$this->panelInit->language['Gender']."</td>
	                              <td>".$data['gender']."</td>
	                          </tr>
	                          <tr>
	                              <td>".$this->panelInit->language['Address']."</td>
	                              <td>".$data['address']."</td>
	                          </tr>
	                          <tr>
	                              <td>".$this->panelInit->language['phoneNo']."</td>
	                              <td>".$data['phoneNo']."</td>
	                          </tr>
	                          <tr>
	                              <td>".$this->panelInit->language['mobileNo']."</td>
	                              <td>".$data['mobileNo']."</td>
	                          </tr>
							  <tr>
	                              <td>".$this->panelInit->language['parent']."</td>
	                              <td>";
								  foreach ($parents as $value) {
									  $return['content'] .= $value->fullName . "<br/>";
								  }
			$return['content'] .= "</td>
	                          </tr>";

                        if (isset($tranportation)) {
                            $return['content'] .= "<tr>
                                <td>".$this->panelInit->language['Transportation']."</td>
                                <td>".$tranportation->transportTitle."</td>
                            </tr>";
                        }

                        $return['content'] .= "<tr>
                                <td>".$this->panelInit->language['Status']."</td>
                                <td>".$usersStatus->statusTitle."</td>
                            </tr>";

                        $return['content'] .= "<tr>
                            <td>Total Paid</td>
                            <td>".number_format($total_paid, 2)."</td>
                        </tr>
                        <tr>
                            <td>Total Unpaid</td>
                            <td>".number_format($total_unpaid, 2)."</td>
                        </tr>";

                        $return['content'] .= "
                        </tbody></table>";
		}else{
			$return['title'] = "Student deleted ";
            $return['content'] = "<div class='text-center'> Student with this id has been deleted </div>";
		}

		return $return;
	}

    function createStudentId() {
        $yearTitle = \academic_year::where('id', $this->panelInit->selectAcYear)->first()->yearTitle;
        $studentCount = \User::where('studentAcademicYear', $this->panelInit->selectAcYear)->count();

        if (!$yearTitle) {
            return false;
        }

        $studentIdExist = true;

        while ($studentIdExist) {
            $newStudentId = str_replace('-', '', $yearTitle).'-'.(++$studentCount);
            $studentIdExist = boolval(\User::where('studentRollId', $newStudentId)->count());
        }

        return $newStudentId;
    }

}
