<?php

    namespace App\Http\Controllers\taskController;

use  App\Http\Controllers\dataget\ListGetController;
use App\Http\Controllers\Message\StatusMessage;
use App\Http\Controllers\Controller;
use App\Http\Controllers\RoleManagement;
use App\Model\BookingFile;
use App\Model\MxpBooking;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Supplier;
use App\MxpIpo;
use Validator;
use Auth;
use DB;
use Illuminate\Support\Facades\Response;
use ZipArchive;
use App\Model\MxpBookingBuyerDetails;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class BookingListController extends Controller
{   
    CONST CREATE_IPO = "create";
    CONST UPDATE_IPO = "update";

    public function bookingListView(){

        $bookingList = DB::table('mxp_bookingBuyer_details')
            ->where('is_complete', 0)
            ->groupBy('booking_order_id')
            ->orderBy('id','DESC')
            ->paginate(15);

        return view('maxim.booking_list.booking_list_page',compact('bookingList'));
    }

    public function bookingFilesDownload(Request $request){

        $fileinfo = BookingFile::get()->where('booking_buyer_id', $request->booking_buyer_id);


        $files = [];
        $oriFiles = [];

        foreach ($fileinfo as $info){
            array_push($files, 'booking_files/'.$info->file_name_server.'.'.$info->file_ext);
            array_push($oriFiles, 'booking_files/'.$info->file_name_original.'.'.$info->file_ext);
        }

        $bbInfo = MxpBookingBuyerDetails::where('id', $request->booking_buyer_id)->first();

        $zipname = $bbInfo->booking_order_id.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipname, ZipArchive::CREATE);

        $i=0;
        foreach ($files as $file) {
            $zip->addFile($file);
            $zip->renameName($file, $oriFiles[$i]);
            $i++;
        }
        $zip->close();

        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename='.$zipname);
        header('Content-Length: ' . filesize($zipname));

        readfile($zipname);

        File::delete($zipname);

        return redirect()->back();
    }

    public function showBookingReport(Request $request){
        $bookingReport = DB::select("call getBookinAndBuyerDeatils('".$request->bid."')");

        $companyInfo = DB::table('mxp_header')->where('header_type',11)->get();

        $gmtsOrSizeGroup = DB::select("SELECT gmts_color,GROUP_CONCAT(item_size) as itemSize,GROUP_CONCAT(item_quantity) as quantity from mxp_booking WHERE booking_order_id = '".$request->bid."' GROUP BY gmts_color");

        return view('maxim.orderInput.reportFile',compact('bookingReport','companyInfo','gmtsOrSizeGroup'));
    }

    public function getBookingListByBookingId(Request $request){

        $bookingList = DB::table('mxp_bookingBuyer_details')
            ->where('booking_order_id', 'like', '%'.$request->booking_id.'%')
            ->orderBy('id','DESC')
            ->get();

        return $bookingList;
    }

    public function getBookingListBySearch(Request $request){

        $bookingList = DB::table('mxp_bookingBuyer_details');
        $checkValidation = false;

        if($request->buyer_name_search != '')
        {
            $checkValidation = true;
            $bookingList->where('buyer_name','like','%'.$request->buyer_name_search.'%');
        }
        if($request->company_name_search != '')
        {
            $checkValidation = true;
            $bookingList->where('Company_name','like','%'.$request->company_name_search.'%');
        }
        if($request->attention_search != '')
        {
            $checkValidation = true;
            $bookingList->where('attention_invoice','like','%'.$request->attention_search.'%');
        }
        if($request->from_oder_date_search != '' && $request->to_oder_date_search != '')
        {
            $checkValidation = true;
            if($request->from_oder_date_search == $request->to_oder_date_search)
                $bookingList->whereDate('created_at', $request->from_oder_date_search);
            else
                $bookingList->whereDate('created_at','>=',$request->from_oder_date_search)->whereDate('created_at','<=',$request->to_oder_date_search);
        }
//      if($request->from_shipment_date_search != '' && $request->to_shipment_date_search != '')
//      {
//          $checkValidation = true;
//          if($request->from_shipment_date_search == $request->to_shipment_date_search)
//              $bookingList->whereDate('created_at', $request->from_shipment_date_search);
//          else
//              $bookingList->whereBetween('created_at', [$request->from_shipment_date_search, $request->to_shipment_date_search]);
//      }

        if($checkValidation)
        {
            $bookings = $bookingList->groupBy('booking_order_id')->orderBy('id','DESC')->get();
            return $bookings;
        }
        else
            return null;
    }

    public function createIpoView(Request $request){
        
        $IpoUniqueID = "RGA".Carbon::now()->format('dmY')."-ACL-02-".mt_rand(10000,99999);

        $bookingDetails = DB::select("select user_id,booking_order_id,erp_code,item_code,item_description,GROUP_CONCAT(item_size) as item_size,GROUP_CONCAT(item_quantity) as item_quantity,item_price,matarial,gmts_color,others_color,orderDate,orderNo,shipmentDate,poCatNo,created_at,updated_at from mxp_booking where booking_order_id= '".$request->booking_id."' GROUP BY item_code");

        if (empty($bookingDetails)) {

            StatusMessage::create('empty_booking_data', 'This booking id empty value !');

            return \Redirect()->Route('booking_list_view');
        }
        $lastIpoIds = [];
        foreach ($bookingDetails as $details) {
            $createIpo = new MxpIpo();
            $createIpo->user_id = Auth::user()->user_id;
            $createIpo->ipo_id = $IpoUniqueID;
//          $createIpo->initial_increase = $ipoIncrease;
            $createIpo->booking_order_id = $details->booking_order_id;
            $createIpo->erp_code = $details->erp_code;
            $createIpo->item_code = $details->item_code;
            $createIpo->item_description = $details->item_description;
            $createIpo->item_size = $details->item_size;
            $createIpo->item_quantity = $details->item_quantity;
            $createIpo->item_price = $details->item_price;
            $createIpo->matarial = $details->matarial;
            $createIpo->gmts_color = $details->gmts_color;
            $createIpo->others_color = $details->others_color;
            $createIpo->orderDate = $details->orderDate;
            $createIpo->orderNo = $details->orderNo;
            $createIpo->shipmentDate = $details->shipmentDate;
            $createIpo->poCatNo = $details->poCatNo;
            $createIpo->status = self::CREATE_IPO;
            $createIpo->save();

            array_push($lastIpoIds, $createIpo->id);


        }

        $ipoDetails = DB::table("mxp_ipo")->where('ipo_id',$IpoUniqueID)->get();
        $buyerDetails = DB::table("mxp_bookingBuyer_details")->where('booking_order_id',$request->booking_id)->get();
        $headerValue = DB::table("mxp_header")->where('header_type',11)->get();
        return view('maxim.ipo.ipo_price_manage',[
            'headerValue' => $headerValue,
            'buyerDetails' => $buyerDetails,
            'sentBillId' => $ipoDetails,
            'ipoIds' => $lastIpoIds
        ]);
    }

    public function createMrfView(Request $request){
        // return 'createMrfView and booking id is '.$request->booking_id;

         // $data = $request->all();

            $suppliers = Supplier::where('status', 1)
                                 ->where('is_delete', 0)
                                 ->get();

            $booking_order_id = $request->booking_id;

            $bookingDetails = DB::select("SELECT * FROM mxp_booking_challan WHERE booking_order_id = '".$request->booking_id."' GROUP BY item_code");

            $buyerDetails = DB::select("SELECT * FROM mxp_bookingBuyer_details WHERE booking_order_id = '".$request->booking_id."'");

            if(empty($bookingDetails)){
                StatusMessage::create('empty_booking_data', 'This booking id empty value !');

                return \Redirect()->Route('booking_list_view');
            }

            $MrfDetails = DB::select("select * from mxp_MRF_table where booking_order_id = '".$request->booking_id."' GROUP BY mrf_id");

            return view('maxim.mrf.mrf',compact('bookingDetails','MrfDetails','booking_order_id', 'suppliers'));
    }

    public function detailsViewForm(Request $request)
    {

        $bookingDetails = MxpBookingBuyerDetails::with('bookings', 'ipo', 'mrf')->where('booking_order_id', $request->booking_id)->first();
//        return $bookingDetails;
        return view('maxim.booking_list.booking_View_Details',['booking_id' => $request->booking_id, 'bookingDetails' => $bookingDetails]/*,compact('bookingList')*/);
    }
}
