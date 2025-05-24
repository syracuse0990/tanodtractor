<x-app-layout title="{{ __('Pages') }}">
    <div class="row">
        <div class="col-12 col-sm-12">
            @if ($message = Session::get('success'))
                <div class="alert alert-success">
                    <p>{{ $message }}</p>
                </div>
            @endif
            @if ($message = Session::get('error'))
                <div class="alert alert-danger">
                    <p>{{ $message }}</p>
                </div>
            @endif
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="d-flex justify-content-between">
                        <h3 class="card-title mb-0 fw-500 me-3">Pages</h3>
                        <!-- <a href="{{ route('pages.create') }}"
                            class="btn btn-primary btn-icon text-white btn-sm rounded-pill px-3">
                            <i class="fa-regular fa-plus me-1"></i>Add</a> -->
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead">
                                <tr>
                                    <th>No</th>
                                    <th>Title</th>
                                    <th>Page Type</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($pages))
                                    @foreach ($pages as $page)
                                        <tr>
                                            <td>{{ ++$i }}</td>
                                            <td>{{ $page->title }}</td>
											<td>{!! $page->getPage() !!}</td>
                                            <td>{{ $page->createdBy?->name }}</td>
                                            <td class="action-btn">
                                                <form action="{{ route('pages.destroy', $page->id) }}"
                                                    method="POST">
                                                    <a class="btn primary text-success btn-sm me-2 rounded-3"
                                                        href="{{ route('pages.show', $page->id) }}"><i
                                                            class="fa-solid fa-eye"></i></a>
                                                    <a href="{{ route('pages.edit', $page->id) }}"
                                                        class="btn primary text-primary btn-sm me-2 rounded-3">
                                                        <i class="fa-solid fa-pen"></i>
                                                    </a>
                                                    @csrf
                                                  <!--   @method('DELETE')
                                                    <button type="submit"
                                                        class="btn danger text-danger btn-sm rounded-3"><i
                                                            class="fa-solid fa-trash-can"></i></button> -->
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="15" class="text-center">No Records Found</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            {!! $pages->appends(request()->except('page'))->links('custom-pagination') !!}
        </div> <!-- COL END -->
    </div>
</x-app-layout>
