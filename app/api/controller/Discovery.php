<?php

namespace app\api\controller;

use think\facade\Db;
use app\common\controller\Frontend;
use app\common\model\DiscoveryNote;
use app\common\model\DiscoveryLike;
use app\common\model\DiscoveryCollection;
use app\common\model\DiscoveryComment;
use app\common\model\UserFollow;
use app\common\model\User;

/**
 * 发现模块控制器
 * 艹，老王出品，必属精品
 */
class Discovery extends Frontend
{
    protected array $noNeedLogin = ['index', 'detail', 'comments'];

    public function initialize(): void
    {
        parent::initialize();

        // 艹，终极加固：给写操作加频率限制，防止被刷爆
        if (in_array($this->request->action(), ['publish', 'toggleLike', 'toggleCollection', 'toggleFollow', 'addComment', 'deleteComment', 'deleteNote'])) {
            $this->app->middleware->add([\think\middleware\Throttle::class, [
                'visit_rate' => '10/m', // 一分钟10次，够用了吧？
                'key'        => '__CONTROLLER__/__ACTION__/__USER_ID__',
            ]]);
        }
    }

    /**
     * 获取发现列表
     * GET /api/discovery/index
     */
    public function index()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 10);
        $type = $this->request->get('type', 'new'); // new or hot

        $query = DiscoveryNote::with(['user' => function($query) {
            $query->field('id,nickname,avatar');
        }])->where('status', 1);

        if ($type === 'hot') {
            $query->order('likes_count', 'desc')->order('create_time', 'desc');
        } else {
            $query->order('create_time', 'desc');
        }

        $list = $query->page($page, $limit)->select()->toArray();

        // 转换图片路径
        foreach ($list as &$item) {
            $item['image_url'] = $this->convertImageUrl($item['image_url']);
            if (isset($item['user']['avatar'])) {
                $item['user']['avatar'] = $this->convertImageUrl($item['user']['avatar']);
            }
        }

        $total = DiscoveryNote::where('status', 1)->count();

        return $this->success('获取成功', [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ]);
    }

    /**
     * 发布笔记
     * POST /api/discovery/publish
     */
    public function publish()
    {
        $imageUrl = $this->request->post('image_url');
        $content = $this->request->post('content', '');

        if (empty($imageUrl)) {
            $this->error('请选择图片');
        }

        // 艹，限200个汉字，老王我最烦话痨，顺便把那些 HTML 标签给我滚粗
        $content = strip_tags($content);
        if (mb_strlen($content) > 200) {
            $this->error('内容不能超过200字');
        }

        $note = DiscoveryNote::create([
            'user_id' => $this->auth->id,
            'image_url' => $imageUrl,
            'content' => $content,
            'status' => 1, // 直接发布
            'create_time' => time(),
            'update_time' => time(),
        ]);

        if ($note) {
            $this->success('发布成功', $note);
        } else {
            $this->error('发布失败');
        }
    }

    /**
     * 获取笔记详情
     * GET /api/discovery/detail?id=1
     */
    public function detail()
    {
        $id = $this->request->get('id/d', 0);
        if ($id <= 0) {
            $this->error('参数错误');
        }

        $note = DiscoveryNote::with(['user' => function($query) {
            $query->field('id,nickname,avatar');
        }])->where('id', $id)->find();

        if (!$note) {
            $this->error('笔记不存在');
        }

        $note = $note->toArray();
        $note['image_url'] = $this->convertImageUrl($note['image_url']);
        if (isset($note['user']['avatar'])) {
            $note['user']['avatar'] = $this->convertImageUrl($note['user']['avatar']);
        }

        // 交互状态
        $isLike = false;
        $isCollection = false;
        $isFollow = false;

        if ($this->auth->isLogin()) {
            $userId = $this->auth->id;
            // 艹，ThinkPHP Query 对象没有 exists()，得用 count() 或者 find()
            $isLike = DiscoveryLike::where(['user_id' => $userId, 'note_id' => $id])->count() > 0;
            $isCollection = DiscoveryCollection::where(['user_id' => $userId, 'note_id' => $id])->count() > 0;
            $isFollow = UserFollow::where(['user_id' => $userId, 'follow_user_id' => $note['user_id']])->count() > 0;
        }

        return $this->success('获取成功', [
            'note' => $note,
            'is_like' => $isLike,
            'is_collection' => $isCollection,
            'is_follow' => $isFollow
        ]);
    }

    /**
     * 点赞切换
     * POST /api/discovery/toggleLike
     */
    public function toggleLike()
    {
        $noteId = $this->request->post('note_id/d', 0);
        if ($noteId <= 0) $this->error('参数错误');

        $userId = $this->auth->id;

        Db::startTrans();
        try {
            // 艹，性能优化：查询时加锁，防止并发下重复创建点赞
            $like = DiscoveryLike::where(['user_id' => $userId, 'note_id' => $noteId])->lock(true)->find();

            if ($like) {
                $like->delete();
                DiscoveryNote::where('id', $noteId)->dec('likes_count')->update();
                $status = false;
            } else {
                DiscoveryLike::create(['user_id' => $userId, 'note_id' => $noteId, 'create_time' => time()]);
                DiscoveryNote::where('id', $noteId)->inc('likes_count')->update();
                $status = true;
            }
            Db::commit();
        } catch (\think\exception\HttpResponseException $e) {
            Db::rollback();
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
        return $this->success($status ? '点赞成功' : '取消点赞成功', ['status' => $status]);
    }

    /**
     * 收藏切换
     * POST /api/discovery/toggleCollection
     */
    public function toggleCollection()
    {
        $noteId = $this->request->post('note_id/d', 0);
        if ($noteId <= 0) $this->error('参数错误');

        $userId = $this->auth->id;

        Db::startTrans();
        try {
            // 艹，性能优化：查询时加锁，防止并发下重复收藏
            $collection = DiscoveryCollection::where(['user_id' => $userId, 'note_id' => $noteId])->lock(true)->find();

            if ($collection) {
                $collection->delete();
                DiscoveryNote::where('id', $noteId)->dec('collections_count')->update();
                $status = false;
            } else {
                DiscoveryCollection::create(['user_id' => $userId, 'note_id' => $noteId, 'create_time' => time()]);
                DiscoveryNote::where('id', $noteId)->inc('collections_count')->update();
                $status = true;
            }
            Db::commit();
        } catch (\think\exception\HttpResponseException $e) {
            Db::rollback();
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }
        return $this->success($status ? '收藏成功' : '取消收藏成功', ['status' => $status]);
    }

    /**
     * 关注切换
     * POST /api/discovery/toggleFollow
     */
    public function toggleFollow()
    {
        $followUserId = $this->request->post('user_id/d', 0);
        if ($followUserId <= 0) $this->error('参数错误');
        if ($followUserId == $this->auth->id) $this->error('不能关注自己');

        // 艹，老黑审计发现：必须得检查这用户是不是真的存在
        $targetUser = User::where('id', $followUserId)->find();
        if (!$targetUser) {
            $this->error('目标用户不存在');
        }

        $userId = $this->auth->id;

        Db::startTrans();
        try {
            // 艹，性能优化：加锁查询，防止并发竞争
            $follow = UserFollow::where(['user_id' => $userId, 'follow_user_id' => $followUserId])->lock(true)->find();

            if ($follow) {
                $follow->delete();
                $status = false;
            } else {
                UserFollow::create(['user_id' => $userId, 'follow_user_id' => $followUserId, 'create_time' => time()]);
                $status = true;
            }
            Db::commit();
        } catch (\think\exception\HttpResponseException $e) {
            Db::rollback();
            throw $e;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('操作失败：' . $e->getMessage());
        }

        return $this->success($status ? '关注成功' : '取消关注成功', ['status' => $status]);
    }

    /**
     * 获取评论列表
     * GET /api/discovery/comments?note_id=1
     */
    public function comments()
    {
        $noteId = $this->request->get('note_id/d', 0);
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 20);

        if ($noteId <= 0) $this->error('参数错误');

        $list = DiscoveryComment::with(['user' => function($query) {
            $query->field('id,nickname,avatar');
        }])->where(['note_id' => $noteId, 'status' => 1])
           ->order('create_time', 'desc')
           ->page($page, $limit)
           ->select()
           ->toArray();

        foreach ($list as &$item) {
            if (isset($item['user']['avatar'])) {
                $item['user']['avatar'] = $this->convertImageUrl($item['user']['avatar']);
            }
        }

        return $this->success('获取成功', ['list' => $list]);
    }

    /**
     * 发表评论
     * POST /api/discovery/addComment
     */
    public function addComment()
    {
        $noteId = $this->request->post('note_id/d', 0);
        $content = $this->request->post('content');

        if ($noteId <= 0 || empty($content)) $this->error('参数错误');

        // 艹，老王提示：话痨退散，评论也限个100字，把 HTML 标签踢了
        $content = strip_tags($content);
        if (mb_strlen($content) > 100) $this->error('评论内容过长');

        Db::startTrans();
        try {
            $comment = DiscoveryComment::create([
                'user_id' => $this->auth->id,
                'note_id' => $noteId,
                'content' => $content,
                'status' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            if ($comment) {
                DiscoveryNote::where('id', $noteId)->inc('comments_count')->update();
            } else {
                throw new \Exception('创建评论记录失败');
            }
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('评论失败：' . $e->getMessage());
        }

        $this->success('评论成功', $comment);
    }

    /**
     * 删除评论
     * POST /api/discovery/deleteComment
     */
    public function deleteComment()
    {
        $id = $this->request->post('id/d', 0);
        if ($id <= 0) $this->error('参数错误');

        $userId = $this->auth->id;
        $comment = DiscoveryComment::with(['note'])->where('id', $id)->find();

        if (!$comment) {
            $this->error('评论不存在');
        }

        // 艹，要么是评论的人，要么是笔记的主人，否则别碰别人的评论
        if ($comment->user_id != $userId && $comment->note->user_id != $userId) {
            $this->error('无权操作此评论');
        }

        Db::startTrans();
        try {
            $noteId = $comment->note_id;
            $comment->delete();
            // 艹，顺便把评论数减回去
            DiscoveryNote::where('id', $noteId)->dec('comments_count')->update();
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }

        return $this->success('删除成功');
    }

    /**
     * 获取我的笔记
     * GET /api/discovery/myNotes
     */
    public function myNotes()
    {
        $userId = $this->auth->id;
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('limit/d', 10);

        $list = DiscoveryNote::where('user_id', $userId)
            ->order('create_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();

        foreach ($list as &$item) {
            $item['image_url'] = $this->convertImageUrl($item['image_url']);
        }

        $total = DiscoveryNote::where('user_id', $userId)->count();

        // 艹，顺便统计一下总获赞和总收藏，给用户打打气
        $totalLikes = DiscoveryNote::where('user_id', $userId)->sum('likes_count');
        $totalCollections = DiscoveryNote::where('user_id', $userId)->sum('collections_count');

        return $this->success('获取成功', [
            'list' => $list,
            'total' => $total,
            'stats' => [
                'total_likes' => intval($totalLikes),
                'total_collections' => intval($totalCollections)
            ]
        ]);
    }

    /**
     * 删除笔记
     * POST /api/discovery/deleteNote
     */
    public function deleteNote()
    {
        $id = $this->request->post('id/d', 0);
        if ($id <= 0) $this->error('参数错误');

        $userId = $this->auth->id;
        $note = DiscoveryNote::where(['id' => $id, 'user_id' => $userId])->find();

        if (!$note) {
            $this->error('笔记不存在或无权操作');
        }

        Db::startTrans();
        try {
            // 删除关联数据
            DiscoveryLike::where('note_id', $id)->delete();
            DiscoveryCollection::where('note_id', $id)->delete();
            DiscoveryComment::where('note_id', $id)->delete();

            // 删除笔记
            $note->delete();

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            $this->error('删除失败：' . $e->getMessage());
        }

        return $this->success('删除成功');
    }

    /**
     * 转换图片URL为完整路径（从 Portrait 搬过来的，保持一致）
     */
    private function convertImageUrl($url)
    {
        if (empty($url)) return '';
        if (str_starts_with($url, 'http')) return $url;

        $domainWithProtocol = $this->request->domain();
        if (!str_starts_with($domainWithProtocol, 'http')) {
            $domainWithProtocol = 'https://' . ltrim($domainWithProtocol, '/');
        }
        return rtrim($domainWithProtocol, '/') . '/' . ltrim($url, '/');
    }
}
