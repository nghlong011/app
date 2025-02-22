@extends('adminlte::page')

@section('content_header', __('admin.apps'))

@section('content')

@include('adminlte::inc.messages')

<div class="row">
    <div class="col-12">
        <div class="btn-group">
            <a href="{{ asset(env('ADMIN_URL').'/apps/create') }}" class="btn button-green mb-3"><i class="fas fa-plus-square"></i>
                @lang('admin.create_app')</a>
            <button class="btn btn-primary mb-3" data-toggle="modal" data-target="#export_modal"><i class="fas fa-download"></i> @lang('admin.import')</button>

        </div>
        <div class="card">

            <div class="table-responsive">
                <table class="table table-hover text-nowrap m-0" id="table" data-delete-prompt-title="@lang('admin.oops')" data-delete-prompt-body="@lang('admin.delete_prompt')" data-yes="@lang('admin.yes')" data-cancel="@lang('admin.cancel')">
                    <thead>
                        <tr>
                            <th class="col-1 text-center">
                                <div class="icheck-wetasphalt">
                                    <input id="check-all" name="check-all" class="check-all" type="checkbox">
                                    <label for="check-all"></label>
                                </div>
                            </th>
                            <th class="col-1">@lang('admin.image')</th>
                            <th class="col-1">@lang('admin.id')</th>
                            <th class="col-4">@lang('admin.title')</th>
                            <th class="col-1">@lang('admin.page_views')</th>
                            <th class="col-1">@lang('admin.versions')</th>
                            <th class="col-2">@lang('admin.date')</th>
                            <th class="col-1"><i class="fas fa-align-justify"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                        @php if(empty($row->image)){ $row->image='no_image.png'; }@endphp
                        <tr id="{{$row->id}}">
                            <td class="text-center">

                                <div class="icheck-wetasphalt">
                                    <input id="app_{{$row->id}}" name="submissions[]" class="check" type="checkbox">
                                    <label for="app_{{$row->id}}"></label>
                                </div>

                            </td>
                            <td><img src="{{ s3_switch("$row->image") }}" class="img-w100"></td>
                            <td>{{$row->id}}</td>
                            <td><a href="{{ asset($settings['app_base']) }}/{{ $row->slug }}" class="text-dark" target="_blank">{{$row->title}}</a></td>
                            <td>{{number_format($row->page_views)}}</td>
                            <td>{{count($row->versions)}}</td>
                            <td>{{\Carbon\Carbon::parse($row->created_at)->translatedFormat('M d, Y H:i')}}</td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn p-0" data-toggle="dropdown" aria-expanded="false" data-boundary="viewport">
                                        <i class="fas fa-align-justify"></i>
                                    </button>
                                    <div class="dropdown-menu mr-3">
                                        <a class="dropdown-item" href="{{ asset($settings['app_base']) }}/{{ $row->slug }}" target="_blank"><i class="fas fa-external-link-alt mr-1"></i> @lang('admin.browse')</a>
                                        <a class="dropdown-item" href="{{ route('apps.edit', $row->id)}}"><i class="fas fa-edit mr-1"></i> @lang('admin.edit')</a>
                                        <a class="dropdown-item" href="{{asset(env('ADMIN_URL') . '/versions/'.$row->id)}}"><i class="fas fa-code-branch mr-1"></i> @lang('admin.versions')</a>
                                        <div class="dropdown-divider"></div>
                                        <form id="delete_from_{{$row->id}}" method="POST" action="{{action('App\Http\Controllers\ApplicationController@destroy', $row['id'])}}">
                                            {{ csrf_field() }}
                                            {{ method_field('DELETE') }}
                                            <a href="javascript:void(0);" data-id="{{$row->id}}" class="_delete_data dropdown-item" role="button"><i class="fas fa-ban mr-1"></i> @lang('admin.delete')</a>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>

                </table>
                @if(!$rows->isEmpty())
                <div class="card-footer clearfix">
                    <button type="submit" class="btn btn-danger" onclick="bulk_delete()">@lang('admin.delete')</button>
                </div>
                @endif

            </div>

        </div>

    </div>
</div>

<div class="modal" id="export_modal" tabindex="-1" role="dialog" aria-labelledby="export_modal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">@lang('admin.import')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="import-form" action="{{ route('import_data_application') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="file">
                    <button type="submit" class="btn btn-primary">@lang('admin.import')</button>
                    <button type="button" id="cancel-import" class="btn btn-danger d-none">Hủy</button>
                </form>
                <div id="execution-time" style="margin-top: 10px; font-weight: bold;"></div>
            </div>
        </div>
    </div>
</div>
@if($rows->isEmpty())
<h6 class="alert alert-warning-custom">@lang('admin.no_records')</h6>
@endif

{{ $rows->onEachSide(1)->links() }}

<script>
let isImporting = false;
let shouldCancel = false;

document.getElementById('import-form').addEventListener('submit', function(event) {
    event.preventDefault();
    
    isImporting = true;
    shouldCancel = false;
    document.getElementById('cancel-import').classList.remove('d-none');
    
    let startTime = Date.now();
    let executionTimeElement = document.getElementById('execution-time');
    executionTimeElement.textContent = 'Đang chuẩn bị dữ liệu...';
    
    let formData = new FormData(this);
    
    fetch("{{ route('import_data_application') }}", {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            executionTimeElement.textContent = `Tổng số dòng: ${data.total_rows}. Bắt đầu xử lý...`;
            processChunks(data.path, 0, data.total_rows);
        }
    })
    .catch(error => {
        isImporting = false;
        document.getElementById('cancel-import').classList.add('d-none');
        executionTimeElement.textContent = 'Có lỗi xảy ra khi tải file!';
        console.error('Error:', error);
    });
});

// Thêm xử lý sự kiện click cho nút cancel
document.getElementById('cancel-import').addEventListener('click', function() {
    shouldCancel = true;
    isImporting = false;
    this.classList.add('d-none');
    document.getElementById('execution-time').textContent = 'Đang hủy quá trình import...';
    
    // Gọi API để xóa file tạm nếu cần
    fetch("{{ route('import_data_application') }}/cancel", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    }).then(() => {
        document.getElementById('execution-time').textContent = 'Đã hủy quá trình import.';
        location.reload();
    });
});

function processChunks(path, chunkIndex, totalRows) {
    if (shouldCancel) {
        document.getElementById('execution-time').textContent = 'Đã hủy quá trình import.';
        return;
    }

    fetch("{{ route('process_chunk') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            path: path,
            chunk_index: chunkIndex
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let executionTimeElement = document.getElementById('execution-time');
            executionTimeElement.textContent = `Đã xử lý ${data.processed_rows}/${totalRows} dòng (${data.progress}%)`;
            
            if (chunkIndex + 1 < totalRows && !shouldCancel) {
                setTimeout(() => {
                    processChunks(path, chunkIndex + 1, totalRows);
                }, 100);
            } else {
                isImporting = false;
                document.getElementById('cancel-import').classList.add('d-none');
                if (!shouldCancel) {
                    executionTimeElement.textContent = 'Hoàn thành import dữ liệu!';
                    setTimeout(() => location.reload(), 2000);
                }
            }
        }
    })
    .catch(error => {
        isImporting = false;
        document.getElementById('cancel-import').classList.add('d-none');
        let executionTimeElement = document.getElementById('execution-time');
        executionTimeElement.textContent = 'Có lỗi xảy ra trong quá trình xử lý!';
        console.error('Error:', error);
    });
}
</script>

@stop