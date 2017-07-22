<?php

namespace App\Http\Controllers;

use App\Space;
use App\Company;
use App\SpaceTypes;
use App\SpaceSchedule;
use App\SpacePrice;
use App\SpaceChecklistItem;
use App\SpaceImage;
use App\User;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Input;
use File;
use DB;
use Image;

class SpaceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $company = Company::where('user_id', '=', Auth::user()->id)->first();

        $spacetypes = SpaceTypes::all(['id', 'name']);
        // return view('space-register')
        //     ->with('spacetypes', $spacetypes);

        $spacetype_checklist = null;

        return view('space-register', ['spacetypes'=>$spacetypes, 'company'=>$company, 'spacetype_checklist'=>$spacetype_checklist]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        /*$uploadcount = 0;

        foreach ($request->images as $img) {
            $uploadcount++;
            print_r($uploadcount);
        }*/

        $space = new Space;

        //$existsCompany = Company::find(Auth::user()->id);

        $existsCompany = Company::where('user_id', '=', Auth::user()->id)->first();

        if($existsCompany === null) {
            $company = new Company;

            /* INSERT COMPANY INFO */
            $company->name = $request->company_name;
            $company->user_id = Auth::user()->id;
            $company->address = $request->company_address;
            $company->country = $request->company_country;
            $company->city = $request->company_city;
            $company->zipcode = $request->company_zipcode;
            $company->nif = $request->company_nif;
            $company->person = $request->company_person;
            $company->phone_number = $request->company_phone_number;
            $company->save();
        } else {
            // UPDATE INFO
            $existsCompany->name = $request->company_name;
            $existsCompany->user_id = Auth::user()->id;
            $existsCompany->address = $request->company_address;
            $existsCompany->country = $request->company_country;
            $existsCompany->city = $request->company_city;
            $existsCompany->zipcode = $request->company_zipcode;
            $existsCompany->nif = $request->company_nif;
            $existsCompany->person = $request->company_person;
            $existsCompany->phone_number = $request->company_phone_number;
            $existsCompany->save();    
        }

        //$company = new Company;
        

        /* INSERT COMPANY INFO */
        // $company->name = $request->company_name;
        // $company->user_id = Auth::user()->id;
        // $company->address = $request->company_address;
        // $company->country = $request->company_country;
        // $company->city = $request->company_city;
        // $company->zipcode = $request->company_zipcode;
        // $company->nif = $request->company_nif;
        // $company->person = $request->company_person;
        // $company->phone_number = $request->company_phone_number;
        // $company->save();

        /* INSERT SPACE INFO */
        $space->user_id = Auth::user()->id;

        if($existsCompany === null) {
            $space->company_id = $company->id;
        } else {
            $space->company_id = $existsCompany->id;
        }
        
        $space->name = $request->space_name;
        $space->type_id = $request->space_type;
        $space->address = $request->space_address;
        $space->zipcode = $request->space_zipcode;
        $space->city = $request->space_city;
        $space->country = $request->space_country;
        $space->description = $request->space_description;
        $space->admin_reviewed = 0;
        $space->save();

        /* INSERT SPACE SCHEDULE INFO */
        for ($x = 1; $x <= 7; $x++) {

            /*print_r('<pre> '. $x . ' ');
            print_r($space->id . ' ');
            print_r($request->is_all_day[$x] . ' ');
            print_r($request->is_closed_day[$x] . '</pre>');*/

            $schedule = new SpaceSchedule;

            $data_is_all_day = Input::get('is_all_day');
            $is_closed_day = Input::get('is_closed_day');


            $schedule->space_id = $space->id;
            $schedule->user_id = Auth::user()->id;
            $schedule->week_day = $x;
            $schedule->open_hour = $request->open[$x];
            $schedule->close_hour = $request->close[$x];
            $schedule->all_day = $data_is_all_day[$x];
            $schedule->closed = $is_closed_day[$x];
            $schedule->save();
        } 

        /* INSERT SPACE HOURLY PRICE INFO */
        for ($x = 1; $x <= 3; $x++) {

            $price = new SpacePrice;

            $price->space_id = $space->id;
            $price->user_id = Auth::user()->id;
            $price->type = $x;
            $price->hour = $request->hour_price[$x];
            $price->hour4 = $request->hour4_price[$x];
            $price->hour8 = $request->hour8_price[$x];
            $price->month = $request->month_price[$x];
            $price->save();
        } 

        /* INSERT CHECKLIST INFO */ 

        

        $space_spaceType = $request->space_type;

        $checklist_count = DB::table('stype_checklist_items')
                ->where('type_id', $space_spaceType)
                ->where('check', '=', 1)
                ->count();
        
        $cl_data = Input::get('cl_item_id');
        // retorna um array com os items que estão checkados ou não (0 e 1)

        for ($x = 1; $x <= $checklist_count; $x++) {
            $space_checklist = new SpaceChecklistItem;

            $getCLITEM = $request->getCLID[$x-1];
            //dd($getCLITEM);
            $cl_check = $cl_data[ $getCLITEM ];

            $space_checklist->space_id = $space->id;
            $space_checklist->stype_checklist_item_id = $getCLITEM;
            $space_checklist->value = $request->cl_checklist_Value[$getCLITEM];
            $space_checklist->status = $cl_check;
            $space_checklist->user_id = Auth::user()->id;
            $space_checklist->save();
        } 


        /* INSERT IMAGES */

        $files = $request->file('images');

        $file_count = count($files);
        $uploadcount = 0;
        //$destinationPath = 'images/spaces/'.$space->id;

        $destinationPath = public_path('/images/spaces/' . $space->id);

        File::makeDirectory($destinationPath, 0775, true, true);

        foreach ($files as $file) {
            $rules = array('images' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048');
            $validator = Validator::make(array('images'=>$file), $rules);

            if($validator->passes()) {
                
                //$filename = date("Y-m-d",time()) . '-' . $files[$uploadcount]->getClientOriginalName();
                //$upload_success = $file->move($destinationPath, $filename);

                // START HERE NEW CODE
                ini_set('memory_limit','256M');

                $extension = $file->getClientOriginalName();
                $thumb = Image::make($file->getRealPath())->encode('jpg', 75)->fit(370, 180, function ($constraint) {
                    $constraint->aspectRatio(); //maintain image ratio
                });
                //$destinationPath = public_path('/images/spaces/' . $space->id);
                $file->move($destinationPath, $extension);
                $thumb->save($destinationPath.'/thumb_'.$extension);
                //$product['imagePath'] = '/uploads/products/'. $username . '/' . $extension;
                //$product['thumbnail'] = '/uploads/products/'. $username . '/thumb_' . $extension;

                // END HERE NEW CODE


                $uploadcount ++;

                //$extension = $file->getClientOriginalExtension();

                $image = new SpaceImage;

                $image->space_id = $space->id;
                $image->user_id = Auth::user()->id;
                $image->img_name = '/images/spaces/'. $space->id . '/' . $extension;
                $image->img_thumb = '/images/spaces/'. $space->id . '/thumb_' . $extension;
                $image->img_type = $file->getClientMimeType();
                $image->img_size = $file->getClientSize();
                $image->save();

            }
        }

        //Set haveSpaces to 1
        $user = User::find(Auth::user()->id);

        if($user) {
            $user->haveSpaces = 1;
            $user->is_admin = 1;
            $user->save();
        }

        return redirect()->back()
            ->with('success','Your Space was created successfully!');

    }

    public function findSpaces()
    {
        /*return view('search')->with('name','Porto');*/
        /*Space::where('name', 'LIKE', "%Porto%")->get();*/

        // $spaces = Space::where('name', 'LIKE', "%$name%")
        //     ->orderBy('name')
        //     ->paginate(20);

        // return view('search')->with(array('list'=>$spaces));

        $space_name = \Request::get('search_name');
        $space_type = \Request::get('type_select');

        //JOIN PARA TRAZER IMAGENS, CHECKLIST, COMPANY, PRICE, SCHEDULE ETC.....
        // $spaces = Space::where('name', 'LIKE', '%'.$search.'%')
        //     -> OrderBy('id')->Paginate(10);


        $spaces = DB::table('spaces')
            -> join('space_types', 'spaces.type_id', '=', 'space_types.id')
            //-> join('space_images', 'spaces.id', '=', 'space_images.space_id')
            -> select('spaces.id','space_types.short_name','spaces.name','spaces.city')
            -> where('spaces.name', 'LIKE', '%'.$space_name.'%')
            -> OrderBy('spaces.id')->Paginate(20);

        $spaceimages = DB::table('spaces')
            -> join('space_images', 'spaces.id', '=', 'space_images.space_id')
            -> select('spaces.id','space_images.img_thumb')
            -> where('spaces.name', 'LIKE', '%'.$space_name.'%')
            ->get();


        return view('search', ['list'=>$spaces, 'listimages'=>$spaceimages]);
    }

    public function listOwnedSpaces() 
    {
        $spaces = DB::table('spaces')
            -> join('space_types', 'spaces.type_id', '=', 'space_types.id')
            -> select('spaces.id','space_types.short_name','spaces.name','spaces.city')
            -> where('user_id', '=', Auth::user()->id)
            -> OrderBy('id')->Paginate(15);

        return view('myspaces',compact('spaces'));
    }

    public function listSpacesForReview() 
    {
        $spaces = DB::table('spaces')
            -> join('space_types', 'spaces.type_id', '=', 'space_types.id')
            -> join('companies', 'spaces.company_id', '=', 'companies.id')
            -> select('spaces.id', 'spaces.name', 'spaces.city', 'space_types.short_name', 'companies.name as company', 'companies.person', 'companies.phone_number')
            -> where('admin_reviewed', '=', 0)
            -> OrderBy('id')->Paginate(15);

        return view('Admin/space_for_review',compact('spaces'));
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //$spaceinfo = Space::find($id);

        $spaceinfo = DB::table('spaces')
            -> join('space_types', 'spaces.type_id', '=', 'space_types.id')
            -> select('spaces.id','space_types.name as type_name','spaces.name','spaces.address','spaces.zipcode','spaces.city','spaces.description')
            -> where('spaces.id', '=', $id)
            ->get();

        $spaceimages = DB::table('space_images')
            -> select('space_images.space_id','space_images.img_name','space_images.img_thumb')
            -> where('space_images.space_id', '=', $id)
            ->get();

        $spaceschedule = DB::table('space_schedules')
            -> select('space_schedules.week_day','space_schedules.open_hour','space_schedules.close_hour', 'space_schedules.all_day', 'space_schedules.closed')
            -> where('space_schedules.space_id', '=', $id)
            -> orderBy('space_schedules.id')
            ->get();

        $spaceprices = DB::table('space_prices')
            -> selectRaw('space_prices.type, ifnull(space_prices.hour,"n/a") as hour, ifnull(space_prices.hour4,"n/a") as hour4, ifnull(space_prices.hour8,"n/a") as hour8, ifnull(space_prices.month,"n/a") as month, ifnull(space_prices.hour,0) + ifnull(space_prices.hour4,0) + ifnull(space_prices.hour8,0) + ifnull(space_prices.month,0) AS have_price_check')
            -> where('space_prices.space_id', '=', $id)
            -> orderBy('space_prices.type')
            ->get();

        $spacechecklist = DB::table('space_checklist_items')
            -> join('checklist_items', 'space_checklist_items.stype_checklist_item_id', '=', 'checklist_items.id')
            -> select('checklist_items.description','checklist_items.label','space_checklist_items.value', 'space_checklist_items.status')
            -> where('space_checklist_items.space_id', '=', $id)
            ->get();

           // dd($spaceprices);
        return view('space', ['spaceinfo'=>$spaceinfo, 'spaceimages'=>$spaceimages, 'spaceschedule'=>$spaceschedule, 'spaceprices'=>$spaceprices ,'spacechecklist'=>$spacechecklist ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $space = Space::findOrFail($id);
        $space->deleted = 1;
        $space->save();

        return redirect()->back();
    }

    public function uploadFiles(Request $request) {
        
        $files = $request->file('files');

        if(!empty($files)):
            $filename = time() . '-' . $files->getClientOriginalName();
        
            $upload_success = $files->move('/images/spaces', $filename);

            if ($upload_success) {
                return Response::json('success', 200);
            } else {
                return Response::json('error', 400);

            }
        endif;      
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showCheckList($itemID)
    {   
            //dd("Im HERE! - ID: " . $itemID);
            $spacetype_checklist = DB::table('checklist_items')
            ->leftJoin('stype_checklist_items', function($join) use ($itemID)
                    {
                        $join->on('checklist_items.id', '=', 'stype_checklist_items.checklist_item_id')
                             ->on('stype_checklist_items.type_id', '=', DB::raw($itemID));

                    })
            ->select('checklist_items.description','checklist_items.haveValue', 'checklist_items.label', 'stype_checklist_items.checklist_item_id')
            -> where('stype_checklist_items.check', '=', 1)
            ->get();
        //dd($spacetype_checklist);
        return view('Admin/Checklist/space_register_checklist')->with('spacetype_checklist', $spacetype_checklist);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function adminSpacePreview($id)
    {
        //$spaceinfo = Space::find($id);

        $spaceinfo = DB::table('spaces')
            -> join('space_types', 'spaces.type_id', '=', 'space_types.id')
            -> select('spaces.id','space_types.name as type_name','spaces.name','spaces.address','spaces.zipcode','spaces.city','spaces.description')
            -> where('spaces.id', '=', $id)
            ->get();

        $spaceimages = DB::table('space_images')
            -> select('space_images.space_id','space_images.img_name','space_images.img_thumb')
            -> where('space_images.space_id', '=', $id)
            ->get();

        $spaceschedule = DB::table('space_schedules')
            -> select('space_schedules.week_day','space_schedules.open_hour','space_schedules.close_hour', 'space_schedules.all_day', 'space_schedules.closed')
            -> where('space_schedules.space_id', '=', $id)
            -> orderBy('space_schedules.id')
            ->get();

        $spaceprices = DB::table('space_prices')
            -> selectRaw('space_prices.type, ifnull(space_prices.hour,"n/a") as hour, ifnull(space_prices.hour4,"n/a") as hour4, ifnull(space_prices.hour8,"n/a") as hour8, ifnull(space_prices.month,"n/a") as month, ifnull(space_prices.hour,0) + ifnull(space_prices.hour4,0) + ifnull(space_prices.hour8,0) + ifnull(space_prices.month,0) AS have_price_check')
            -> where('space_prices.space_id', '=', $id)
            -> orderBy('space_prices.type')
            ->get();

        $spacechecklist = DB::table('space_checklist_items')
            -> join('checklist_items', 'space_checklist_items.stype_checklist_item_id', '=', 'checklist_items.id')
            -> select('checklist_items.description','checklist_items.label','space_checklist_items.value', 'space_checklist_items.status')
            -> where('space_checklist_items.space_id', '=', $id)
            ->get();

           // dd($spaceprices);
        return view('Admin/preview_space', ['spaceinfo'=>$spaceinfo, 'spaceimages'=>$spaceimages, 'spaceschedule'=>$spaceschedule, 'spaceprices'=>$spaceprices ,'spacechecklist'=>$spacechecklist ]);
    }

    /**
     * Accept space.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function acceptSpace($id)
    {
        $space = Space::findOrFail($id);
        $space->admin_reviewed = 1;
        $space->save();

        $spaces = DB::table('spaces')
            -> join('space_types', 'spaces.type_id', '=', 'space_types.id')
            -> join('companies', 'spaces.company_id', '=', 'companies.id')
            -> select('spaces.id', 'spaces.name', 'spaces.city', 'space_types.short_name', 'companies.name as company', 'companies.person', 'companies.phone_number')
            -> where('admin_reviewed', '=', 0)
            -> OrderBy('id')->Paginate(15);

        return view('Admin/space_for_review',compact('spaces'));
    }

}
