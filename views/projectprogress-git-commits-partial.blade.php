@if(empty($gitCommits))
<div class="alert alert-info">
    <i class="fa fa-info-circle"></i> 未找到Git提交记录，请检查Git仓库配置。
</div>
@else
<style>
    .git-commits-table {
        table-layout: fixed;
        width: 100%;
    }
    .git-commits-table th.col-subject {
        width: 30% !important;
    }
    .git-commits-table th.col-hash {
        width: 10% !important;
    }
    .git-commits-table th.col-author {
        width: 8% !important;
    }
    .git-commits-table th.col-date {
        width: 15% !important;
    }
    .git-commits-table th.col-branch {
        width: 37% !important;
    }
    .git-commits-table td.branch-column {
        word-wrap: break-word;
        white-space: normal;
    }
    .git-commits-table td.narrow-column {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>
<div class="table-responsive">
    <table class="table table-striped table-hover git-commits-table">
        <colgroup>
            <col style="width: 30%;">
            <col style="width: 10%;">
            <col style="width: 8%;">
            <col style="width: 15%;">
            <col style="width: 37%;">
        </colgroup>
        <thead>
            <tr>
                <th class="col-subject">提交信息</th>
                <th class="col-hash">提交哈希</th>
                <th class="col-author">作者</th>
                <th class="col-date">提交日期</th>
                <th class="col-branch">分支/标签</th>
            </tr>
        </thead>
        <tbody>
            @foreach($gitCommits as $commit)
            <tr class="commit-row" data-commit-hash="{{ $commit['hash'] }}">
                <td class="commit-subject narrow-column">{{ $commit['subject'] }}</td>
                <td class="commit-hash narrow-column">{{ $commit['short_hash'] }}</td>
                <td class="narrow-column">{{ $commit['author'] }}</td>
                <td class="narrow-column">{{ $commit['date'] }}</td>
                <td class="branch-column">
                    @foreach($commit['branches'] as $branch)
                    <span class="badge badge-{{ $branch['color'] }} mb-1 mr-1 d-inline-block">{{ $branch['name'] }}</span>
                    @endforeach
                    
                    @foreach($commit['tags'] as $tag)
                    <span class="badge badge-warning mb-1 mr-1 d-inline-block">{{ $tag }}</span>
                    @endforeach
                </td>
            </tr>
            <tr id="details-{{ $commit['short_hash'] }}" class="commit-details-row d-none">
                <td colspan="5">
                    <div class="p-2 bg-light">
                        <h6>变更文件列表:</h6>
                        <div class="file-changes">
                            @foreach($commit['files'] as $file)
                            <div class="file-item">
                                @if($file['status'] == 'A')
                                <span class="badge badge-success">添加</span>
                                @elseif($file['status'] == 'M')
                                <span class="badge badge-primary">修改</span>
                                @elseif($file['status'] == 'D')
                                <span class="badge badge-danger">删除</span>
                                @else
                                <span class="badge badge-secondary">{{ $file['status'] }}</span>
                                @endif
                                {{ $file['path'] }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- 分页控件 -->
@if(isset($pagination) && $pagination['total_pages'] > 1)
<nav aria-label="Git提交记录分页">
    <ul class="pagination justify-content-center">
        @for($i = 1; $i <= $pagination['total_pages']; $i++)
        <li class="page-item {{ $i == $pagination['page'] ? 'active' : '' }}">
            <a class="page-link" href="{{ str_replace('&amp;', '&', '?project=' . $selectedProject . '&page=' . $i . '&per_page=' . $pagination['per_page']) }}">{{ $i }}</a>
        </li>
        @endfor
    </ul>
</nav>
@endif
@endif 