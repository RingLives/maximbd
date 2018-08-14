@extends('layouts.dashboard')
@section('page_heading', trans("others.new_mrf_create_label"))
@section('section')
<style type="text/css">
	/*.top-div{
		background-color: #f9f9f9;
		padding:5px 0px 5px 10px;
		border-radius: 7px;
		box-sizing: border-box;
		display: block;
	}
	.showMrfList{
		background-color: #f9f9f9;
		border-radius: 10px;
		box-sizing: border-box;
		box-shadow: 0 10px 20px rgba(0,0,0,0.10), 0 6px 6px rgba(0,0,0,0.15);
		z-index: 999;
	}
	.top-div .mrfControl{
		text-align: left;
		width:30%;
		display: inline-block;
	}
	.top-div .mrfControl .all{
		display: inline;
		float: left;
		width: 10%;
	}

	@media (max-width: 300px) {
		.top-div .mrfControl{
		text-align: left;
		width:40%;
		display: inline-block;
	}
	.top-div .mrfControl .all{
		display: inline;
		float: left;
		width: 25%;
	}
	}*/
</style>

    <div class="container-fluid">
    	@if ($errors->any())
            <div class="alert alert-danger">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


    	@if(Session::has('erro_challan'))
            @include('widgets.alert', array('class'=>'danger', 'message'=> Session::get('erro_challan') ))
		@endif
		<div class="row">
			<div class="col-md-12 col-md-offset-0">
				@if(!empty($MrfDetails))
					<div class="panel showMrfList">
						<div class="panel-heading">MRP list</div>
						<div class="panel-body">
							<table class="table table-striped table-bordered">
								<thead>
									<tr>
										<th>#</th>
										<th>Booking Id</th>
										<th>MRF Id</th>
										<th>Action</th>
									</tr>
								</thead>
								<tbody>
									@php($i=1)
									@foreach($MrfDetails as $details)
									<tr>
										<td>{{$i++}}</td>
										<td>{{$details->booking_order_id}}</td>
										<td>{{$details->mrf_id}}</td>
										<td>
											<form action="{{Route('mrf_list_action_task') }}" role="form" target="_blank">
												<input type="hidden" name="mid" value="{{$details->mrf_id}}">
												<input type="hidden" name="bid" value="{{$details->booking_order_id}}">
												<button class="btn btn-success" >View</button>
											</form>
										</td>
									</tr>
									@endforeach
								</tbody>
							</table>
						</div>
					</div>
				@endif
				<div class="panel panel-default">
					<div class="panel-heading">{{trans('others.new_mrf_create_label')}}</div>
					<div class="panel-body aaa">
							@if(!empty($bookingDetails))
								<form class="form-horizontal" role="form" method="POST" action="{{ Route('mrf_action_task') }}">
									<input type="hidden" name="_token" value="{{ csrf_token() }}">
									<input type="hidden" name="booking_order_id" value="{{$booking_order_id}}">

									<div class="col-sm-6">
										<div class="form-group">
											<label class="col-sm-12 label-control">Supplier</label>
											<div class="col-sm-12">
												{{--<input class="form-control" type="text" name="mrf_person_name" placeholder="Enter Name" required>--}}
												<select class="form-control" name="supplier_id">
													@foreach($suppliers as $supplier)
														<option value="{{$supplier->supplier_id}}">{{$supplier->name}}</option>
													@endforeach
												</select>
											</div>
										</div>
									</div>
									<div class="col-sm-6">
										<div class="form-group">
											<label class="col-sm-12 label-control">Shipment Date</label>
											<div class="col-sm-12">
												<input class="form-control" type="Date" name="mrf_shipment_date" required>
											</div>
										</div>
									</div>
									<table class="table table-bordered table-striped" >
										<thead>
											<tr>
												<th width="4%">#</th>
												<th width="15%">ERP Code</th>
												<th width="15%">Item Code</th>
												<th width="15%">Color</th>
												<th width="15%">Size</th>
												<th width="15%">Quantity Left</th>
												<th>MRF QTY</th>
											</tr>
										</thead>
										<?php
										   $i=1;
										 ?>
										@foreach ($bookingDetails as $item)
											<?php
							    				$itemsize = explode(',', $item->item_size);
                                            	$gmts_color = explode(',', $item->gmts_color);
							    				$qty = explode(',', $item->left_mrf_ipo_quantity);
							    				$mrf_quantity = explode(',', $item->mrf_quantity);

							    				$itemQtyValue = array_combine($itemsize, $qty);
							    			?>
										<tbody>
											<tr>
												<td>
													{{$i++}}
												</td>
												<td><span>{{$item->erp_code}}</span></td>
												<td><span>{{$item->item_code}}</span></td>
												<td class="colspan-td" colspan="4">

													<table id="sampleTbl">
														<tbody>

																@foreach ($qty as $key => $leftQuantity)
																	<tr>
																		<td style="width: 25%;">{{$gmts_color[$key]}}</td>
																		<td style="width: 25%;">{{$itemsize[$key]}}</td>
																		<td style="width: 25%;">
																			<input type="hidden" name="allId[]" value="{{$item->id}}">
																			<input style="" type="text" class="form-control item_quantity" name="product_qty[]" value="{{$leftQuantity}}" >
																		</td>
																		<td style="width: 25%;">
																			<input type="text" class="form-control item_mrf" name="item_mrf[]" value="{{$mrf_quantity[$key]}}" disabled="true">
																		</td>
																	</tr>
																@endforeach
														</tbody>
													</table>
												</td>

											</tr>
										</tbody>
										@endforeach
									</table>
									<div class="form-group ">
										<div class="col-md-6 col-md-offset-10">
											<button type="submit" class="btn btn-primary" id="rbutton">
												{{trans('others.create_button_lable')}}
											</button>
										</div>
									</div>
								</form>
							@if(!empty($multipleChallanList))
								<span style="font-size:15px;">Multiple Challan list</span>
								<table class="table table-bordered">
									<thead>
										<th>Serial No</th>
										<th>Invo no</th>
										<th>Challan no</th>
									</thead>
									@php($k = 1)
									@foreach($multipleChallanList as $ChallanList)
										<tr>
											<td>{{$k++}}</td>
											<td>{{$ChallanList->bill_id}}</td>
											<td>{{$ChallanList->challan_id}}</td>
										</tr>
									@endforeach
								</table>
							@endif
						@endif
					</div>
				</div>
			</div>
		</div>
	</div>
@stop