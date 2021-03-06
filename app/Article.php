<?php

namespace App;

use Emojione\Client;
use Emojione\Ruleset;
use App\ES\ArticleRule;
use ScoutElastic\Searchable;
use App\Events\ArticleCreated;
use App\Traits\TopArticleTrait;
use App\Traits\ArticleCacheTrait;
use App\ES\ArticleIndexConfigurator;
use App\Traits\ArticleTrendingTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Fico7489\Laravel\Pivot\Traits\PivotEventTrait;

/**
 * Class Article
 * @package App
 *
 * @method static static|Builder visible()
 * @method static static|Builder whole(bool $all)
 */
class Article extends Model
{
    use Searchable,
        TopArticleTrait,
        PivotEventTrait,
        ArticleCacheTrait,
        ArticleTrendingTrait;

    /**
     * @var string
     */
    protected $indexConfigurator = ArticleIndexConfigurator::class;

    /**
     * @var array
     */
    protected $searchRules = [
        ArticleRule::class,
    ];

    /**
     * @var array
     */
    protected $mapping = [
        'properties' => [
            'author'           => [
                'properties' => [
                    'id'   => ['type' => 'keyword'],
                    'name' => [
                        'type'            => 'text',
                        'analyzer'        => 'ik_max_word',
                        'search_analyzer' => 'ik_max_word',
                    ],
                ],
            ],
            'article_category' => [
                'properties' => [
                    'id'   => ['type' => 'keyword'],
                    'name' => [
                        'type'            => 'text',
                        'analyzer'        => 'ik_max_word',
                        'search_analyzer' => 'ik_max_word',
                    ],
                ],
            ],
            'content'          => [
                'type'            => 'text',
                'analyzer'        => 'ik_max_word',
                'search_analyzer' => 'ik_max_word',
                'fields'          => [
                    'raw' => [
                        'type'         => 'keyword',
                        'ignore_above' => 256,
                    ],
                ],
            ],
            'title'            => [
                'type'            => 'text',
                'analyzer'        => 'ik_max_word',
                'search_analyzer' => 'ik_max_word',
            ],
            'desc'             => [
                'type'            => 'text',
                'analyzer'        => 'ik_max_word',
                'search_analyzer' => 'ik_max_word',
            ],
            'tags'             => ['type' => 'text'],
            'display'          => ['type' => 'keyword'],
        ],
    ];

    /**
     * @var array
     */
    protected $appends = ['highlight_content'];

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @var array
     */
    protected $dates = [
        'top_at',
    ];

    /**
     * @var array
     */
    protected $casts = [
        'display' => 'boolean',
    ];

    /**
     *
     * @author duc <1025434218@qq.com>
     */
    public static function boot()
    {
        parent::boot();

        static::created(function (Article $article) {
            if ($article->display) {
                event(new ArticleCreated($article->load('author')));
            }
        });

        static::deleted(function (Article $article) {
            $article->comments->each->delete();
        });

        static::pivotAttached(function (Article $article) {
            if (! $article->shouldBeSearchable()) {
                $article->unsearchable();

                return;
            }

            $article->searchable();
        });
    }

    /**
     * @param Builder $query
     * @return Builder
     *
     * @author duc <1025434218@qq.com>
     */
    public function scopeVisible(Builder $query)
    {
        return $query->where('display', true);
    }

    /**
     * @return array
     *
     * @author duc <1025434218@qq.com>
     */
    public function toSearchableArray()
    {
        $model = $this->load(['category', 'author', 'tags']);

        $result = [
            'author'           => [
                'id'   => $model->author->id,
                'name' => $model->author->name,
            ],
            'article_category' => [
                'id'   => $model->category->id,
                'name' => $model->category->name,
            ],
            'content'          => $model->content_md,
            'title'            => $model->title,
            'desc'             => $model->desc,
            'tags'             => $model->tags()->pluck('name')->toArray(),
            'display'          => $model->display,
        ];

        return $result;
    }

    /**
     * @param Builder $q
     * @param  bool  $all
     * @return mixed
     *
     * @author duc <1025434218@qq.com>
     */
    public function scopeWhole($q, bool $all)
    {
        if ($all) {
            return $q;
        } else {
            return $q->where('author_id', \Auth::id());
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author duc <1025434218@qq.com>
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     *
     * @author duc <1025434218@qq.com>
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *
     * @author duc <1025434218@qq.com>
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     *
     * @author duc <1025434218@qq.com>
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->latest();
    }

    /**
     * @return mixed
     *
     * @author duc <1025434218@qq.com>
     */
    public function getRecommendArticles()
    {
        return static::visible()->where('category_id', $this->category_id)
            ->inRandomOrder()
            ->take(3)
            ->get(['id', 'title'])
            ->toArray();
    }

    /**
     * @return null
     *
     * @author duc <1025434218@qq.com>
     */
    public function getContentHtmlAttribute()
    {
        $arr = json_decode($this->content);

        if (is_object($arr)) {
            return $arr->html;
        } else {
            return null;
        }
    }

    /**
     * @return null
     *
     * @author duc <1025434218@qq.com>
     */
    public function getContentMdAttribute()
    {
        $arr = json_decode($this->content);

        if (is_object($arr)) {
            return $arr->md;
        } else {
            return null;
        }
    }

    /**
     * @return array
     *
     * @author duc <1025434218@qq.com>
     */
    public function getHighlightContentAttribute()
    {
        $h = optional($this->highlight);

        $categoryField = 'article_category.name';

        return [
            'content'  => is_null($h->content) ? null : implode('......', $h->content),
            'desc'     => is_null($h->desc) ? null : implode('......', $h->desc),
            'title'    => $h->title[0],
            'category' => $h->{$categoryField}[0],
            'tags'     => is_null($h->tags) ? null : implode(',', $h->tags),
        ];
    }

    /**
     * @param $value
     *
     * @author duc <1025434218@qq.com>
     */
    public function setContentAttribute($value)
    {
        // 应用自定义转化规则
        $value = app()->makeWith('rule', [$value])->apply();

        // 解析成 html 格式
        $parsedown = new \Parsedown();
        $mdContent = $parsedown->text($value);

        // 表情转化处理
        $client = new Client(new Ruleset());
        $content = $client->shortnameToUnicode($mdContent);

        $this->attributes['content'] = json_encode(
            [
                'html' => $content,
                'md'   => $value,
            ]
        );
    }

    public function __get($key)
    {
        if (! $key) {
            return null;
        }
        if (in_array($key, $this->getHidden())) {
            return null;
        }

        return parent::__get($key);
    }
}
