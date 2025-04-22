<?php

namespace Iyuu\BittorrentClient\Driver\qBittorrent;

use Iyuu\BittorrentClient\Clients;
use Iyuu\BittorrentClient\Contracts\Torrent;
use Iyuu\BittorrentClient\Exception\NotFoundException;
use Iyuu\BittorrentClient\Exception\ServerErrorException;
use Ledc\Curl\Curl;
use RuntimeException;

/**
 * qBittorrent
 * @link https://github.com/qbittorrent/qBittorrent
 */
class Client extends Clients
{
    /**
     * 暂停上传
     */
    public const string STATE_pausedUP = 'pausedUP';
    /**
     * API主版本号
     * @var string
     */
    private string $api_version = '2';

    /**
     * CSRF使用的Session或者Cookie
     * @var string
     */
    private string $session_id = '';

    /**
     * 分隔符
     * @var string
     */
    protected string $delimiter = '';

    /**
     * @var string
     */
    private string $clientUrl = '';

    /**
     * 各版的API接入点
     * @var array
     */
    private array $endpoints = [
        'login' => [
            '1' => '/login',
            '2' => '/api/v2/auth/login'
        ],
        'logout' => [
            '1' => null,
            '2' => '/api/v2/auth/logout'
        ],
        'app_version' => [
            '1' => '/version/qbittorrent',
            '2' => '/api/v2/app/version'
        ],
        'api_version' => [
            '1' => '/version/api',
            '2' => '/api/v2/app/webapiVersion'
        ],
        'build_info' => [
            '1' => null,
            '2' => '/api/v2/app/buildInfo'
        ],
        'preferences' => [
            '1' => null,
            '2' => '/api/v2/app/preferences'
        ],
        'setPreferences' => [
            '1' => null,
            '2' => '/api/v2/app/setPreferences'
        ],
        'defaultSavePath' => [
            '1' => null,
            '2' => '/api/v2/app/defaultSavePath'
        ],
        'downloadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/downloadLimit'
        ],
        'setDownloadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/setDownloadLimit'
        ],
        'uploadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/uploadLimit'
        ],
        'setUploadLimit' => [
            '1' => null,
            '2' => '/api/v2/transfer/setUploadLimit'
        ],
        'torrent_list' => [
            '1' => null,
            '2' => '/api/v2/torrents/info'
        ],
        'torrent_properties' => [
            '1' => null,
            '2' => '/api/v2/torrents/properties'
        ],
        'torrent_trackers' => [
            '1' => null,
            '2' => '/api/v2/torrents/trackers'
        ],
        'torrent_files' => [
            '1' => null,
            '2' => '/api/v2/torrents/files'
        ],
        'torrent_pieceStates' => [
            '1' => null,
            '2' => '/api/v2/torrents/pieceStates'
        ],
        'torrent_pieceHashes' => [
            '1' => null,
            '2' => '/api/v2/torrents/pieceHashes'
        ],
        'torrent_pause' => [
            '1' => null,
            '2' => '/api/v2/torrents/pause'
        ],
        'torrent_resume' => [
            '1' => null,
            '2' => '/api/v2/torrents/resume'
        ],
        'torrent_delete' => [
            '1' => null,
            '2' => '/api/v2/torrents/delete'
        ],
        'torrent_recheck' => [
            '1' => null,
            '2' => '/api/v2/torrents/recheck'       // 重新校验种子
        ],
        'torrent_reannounce' => [
            '1' => null,
            '2' => '/api/v2/torrents/reannounce'    // 重新宣告种子
        ],
        'torrent_add' => [
            '1' => null,
            '2' => '/api/v2/torrents/add'
        ],
        'torrent_addTrackers' => [
            '1' => null,
            '2' => '/api/v2/torrents/addTrackers'
        ],
        'torrent_editTracker' => [
            '1' => null,
            '2' => '/api/v2/torrents/editTracker'
        ],
        'torrent_removeTrackers' => [
            '1' => null,
            '2' => '/api/v2/torrents/removeTrackers'
        ],
        'torrent_addPeers' => [
            '1' => null,
            '2' => '/api/v2/torrents/addPeers'
        ],
        'torrent_increasePrio' => [
            '1' => null,
            '2' => '/api/v2/torrents/increasePrio'
        ],
        'torrent_decreasePrio' => [
            '1' => null,
            '2' => '/api/v2/torrents/decreasePrio'
        ],
        'torrent_downloadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/downloadLimit'
        ],
        'torrent_setDownloadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/setDownloadLimit'
        ],
        'torrent_setShareLimits' => [
            '1' => null,
            '2' => '/api/v2/torrents/setShareLimits'
        ],
        'torrent_uploadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/uploadLimit'
        ],
        'torrent_setUploadLimit' => [
            '1' => null,
            '2' => '/api/v2/torrents/setUploadLimit'
        ],
        'torrent_setLocation' => [
            '1' => null,
            '2' => '/api/v2/torrents/setLocation'
        ],
        'torrent_rename' => [
            '1' => null,
            '2' => '/api/v2/torrents/rename'
        ],
        'torrent_setCategory' => [
            '1' => null,
            '2' => '/api/v2/torrents/setCategory'
        ],
        'torrent_categories' => [
            '1' => null,
            '2' => '/api/v2/torrents/categories'
        ],
        'torrent_createCategory' => [
            '1' => null,
            '2' => '/api/v2/torrents/createCategory'
        ],
        'torrent_editCategory' => [
            '1' => null,
            '2' => '/api/v2/torrents/editCategory'
        ],
        'torrent_removeCategories' => [
            '1' => null,
            '2' => '/api/v2/torrents/removeCategories'
        ],
        'torrent_addTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/addTags'
        ],
        'torrent_removeTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/removeTags'
        ],
        'torrent_tags' => [
            '1' => null,
            '2' => '/api/v2/torrents/tags'
        ],
        'torrent_createTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/createTags'
        ],
        'torrent_deleteTags' => [
            '1' => null,
            '2' => '/api/v2/torrents/deleteTags'
        ],
        'torrent_setAutoManagement' => [
            '1' => null,
            '2' => '/api/v2/torrents/setAutoManagement'
        ],
        'torrent_toggleSequentialDownload' => [
            '1' => null,
            '2' => '/api/v2/torrents/toggleSequentialDownload'
        ],
        'torrent_toggleFirstLastPiecePrio' => [
            '1' => null,
            '2' => '/api/v2/torrents/toggleFirstLastPiecePrio'
        ],
        'torrent_setForceStart' => [
            '1' => null,
            '2' => '/api/v2/torrents/setForceStart'
        ],
        'torrent_setSuperSeeding' => [
            '1' => null,
            '2' => '/api/v2/torrents/setSuperSeeding'
        ],
        'torrent_renameFile' => [
            '1' => null,
            '2' => '/api/v2/torrents/renameFile'
        ],
        'maindata' => [
            '1' => null,
            '2' => '/api/v2/sync/maindata'
        ],
    ];

    protected function initialize(): void
    {
        $this->clientUrl = rtrim($this->getConfig()->getClientUrl(), '/');
        if (!$this->login()) {
            throw new RuntimeException("下载器登录失败 qBittorrent Unable to authenticate with Web Api.");
        }
    }

    /**
     * 添加种子到下载器
     * @param Torrent $torrent
     * @return string|bool|null
     * @throws ServerErrorException
     */
    public function addTorrent(Torrent $torrent): string|bool|null
    {
        $parameters = $torrent->parameters;
        if ($torrent->isMetadata()) {
            if (empty($parameters['name']) || empty($parameters['filename'])) {
                $parameters['name'] = 'torrents';
                $parameters['filename'] = time() . '.torrent';
            }
            return $this->addTorrentByMetadata($torrent->payload, $torrent->savePath, $parameters);
        }
        return $this->addTorrentByUrl($torrent->payload, $torrent->savePath, $parameters);
    }

    /**
     * app编译版本
     * @return string
     * @throws ServerErrorException
     */
    public function appVersion(): string
    {
        return $this->getData('app_version');
    }

    /**
     * api版本
     * @return string
     * @throws ServerErrorException
     */
    public function apiVersion(): string
    {
        return $this->getData('api_version');
    }

    /**
     * 编译信息
     * @return array|string
     * @throws ServerErrorException
     */
    public function buildInfo(): array|string
    {
        return $this->getData('build_info');
    }

    /**
     * 登录
     * @return bool
     */
    public function login(): bool
    {
        $curl = $this->getCurl();
        $config = $this->getConfig();
        $curl->post($this->clientUrl . $this->endpoints['login'][$this->api_version], [
            'username' => $config->username ?? '',
            'password' => $config->password ?? ''
        ]);

        // Find authentication cookie and set in curl connection
        foreach ($curl->response_headers as $header) {
            if (preg_match('/SID=(\S[^;]+)/', $header, $matches)) {
                $this->session_id = $matches[0];
                $qb415 = '; QB_' . $this->session_id;   // 兼容qBittorrent v4.1.5[小钢炮等]
                $this->session_id = $this->session_id . $qb415;
                $curl->setHeader('Cookie', $this->session_id);
                return true;
            }
        }

        if ($config->is_debug) {
            var_dump($curl->request_headers);
            var_dump($curl->response_headers);
        }

        return false;
    }

    /**
     * 退出登录
     * @return self
     * @throws ServerErrorException
     */
    public function logout(): static
    {
        $this->getData('logout');
        $this->session_id = '';
        $this->curl->reset();
        return $this;
    }

    /**
     * 获取下载器首选项
     * @return string|false|null
     * @throws ServerErrorException
     */
    public function preferences(): string|null|false
    {
        return $this->getData('preferences');
    }

    /**
     * 设置下载器首选项
     * @param array $data
     * @return string|array|false|null
     * @throws ServerErrorException
     */
    public function setPreferences(array $data = []): string|array|null|false
    {
        if (!empty($data)) {
            return $this->postData('setPreferences', ['json' => json_encode($data)]);
        }
        return [];
    }

    /**
     * 获取种子列表
     * @return string|false|null
     * @throws ServerErrorException
     */
    public function torrentList(): string|null|false
    {
        return $this->getData('torrent_list');
    }

    /**
     * 添加种子链接
     * @param string $torrent_url
     * @param string $save_path
     * @param array $extra_options
     * array(
     * 'urls'    =>  '',
     * 'savepath'    =>  '',
     * 'cookie'    =>  '',
     * 'category'    =>  '',
     * 'skip_checking'    =>  true,
     * 'paused'    =>  true,
     * 'root_folder'    =>  true,
     * )
     * @return array|string
     * @throws ServerErrorException
     */
    public function addTorrentByUrl(string $torrent_url = '', string $save_path = '', array $extra_options = []): array|string
    {
        if (!empty($save_path)) {
            $extra_options['savepath'] = $save_path;
        }
        $extra_options['urls'] = $torrent_url;
        #$extra_options['skip_checking'] = 'true';    //跳校验
        // 关键 上传文件流 multipart/form-data【严格按照api文档编写】
        $post_data = $this->buildData($extra_options);
        #p($post_data);
        $curl = $this->initCurl();
        // 设置请求头
        $curl->setHeader('Content-Type', 'multipart/form-data; boundary=' . $this->delimiter);
        $curl->setHeader('Content-Length', strlen($post_data));
        return $this->postData('torrent_add', $post_data, $curl);
    }

    /**
     * 添加种子元数据
     * @param string $torrent_metainfo
     * @param string $save_path
     * @param array $extra_options
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function addTorrentByMetadata(string $torrent_metainfo = '', string $save_path = '', array $extra_options = []): false|string|null
    {
        if (!empty($save_path)) {
            $extra_options['savepath'] = $save_path;
        }
        $extra_options['torrents'] = $torrent_metainfo;
        #$extra_options['skip_checking'] = 'true';    //跳校验
        // 关键 上传文件流 multipart/form-data【严格按照api文档编写】
        $post_data = $this->buildTorrent($extra_options);
        $curl = $this->initCurl();
        // 设置请求头
        $curl->setHeader('Content-Type', 'multipart/form-data; boundary=' . $this->delimiter);
        $curl->setHeader('Content-Length', strlen($post_data));
        return $this->postData('torrent_add', $post_data, $curl);
    }

    /**
     * 删除所有种子
     * @param bool $deleteFiles
     * @return string
     * @throws ServerErrorException
     */
    public function deleteAll(bool $deleteFiles = false): string
    {
        $torrents = json_decode($this->torrentList());
        $response = '';
        foreach ($torrents as $torrent) {
            $response .= $this->delete($torrent->hash, $deleteFiles);
        }

        return $response;
    }

    /**
     * 暂停种子
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function pause(string $hash): false|string|null
    {
        return $this->postData('torrent_pause', ['hashes' => $hash]);
    }

    /**
     * 恢复做种
     * @param string|array $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function resume(string|array $hash): false|string|null
    {
        return $this->postData('torrent_resume', ['hashes' => is_string($hash) ? $hash : implode('|', $hash)]);
    }

    /**
     * 抽象方法，子类实现
     * 删除种子
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @param bool $deleteFiles 是否同时删除数据
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function delete(string $hash = '', bool $deleteFiles = false): false|string|null
    {
        return $this->postData('torrent_delete', ['hashes' => $hash, 'deleteFiles' => $deleteFiles ? 'true' : 'false']);
    }

    /**
     * 重新校验种子
     * @param string|array $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function recheck(string|array $hash): false|string|null
    {
        return $this->postData('torrent_recheck', [
            'hashes' => is_string($hash) ? $hash : implode('|', $hash),
        ]);
    }

    /**
     * 重新宣告种子
     * @param string $hash info_hash可以|分隔，删除多个种子；也可以传入all，删除所有种子
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function reannounce(string $hash): false|string|null
    {
        return $this->postData('torrent_reannounce', ['hashes' => $hash]);
    }

    /**
     * 给种子打标签
     * @param string|array $hashes
     * @param string|array $tags
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function torrentAddTags(string|array $hashes, string|array $tags = 'IYUU'): false|string|null
    {
        return $this->postData('torrent_addTags', [
            'hashes' => is_string($hashes) ? $hashes : implode('|', $hashes),
            'tags' => is_string($tags) ? $tags : implode(',', $tags)
        ]);
    }

    /**
     * 移除种子的标签
     * @param string|array $hashes
     * @param string|array $tags
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function torrentRemoveTags(string|array $hashes, string|array $tags = 'IYUU'): false|string|null
    {
        return $this->postData('torrent_removeTags', [
            'hashes' => is_string($hashes) ? $hashes : implode('|', $hashes),
            'tags' => is_string($tags) ? $tags : implode(',', $tags)
        ]);
    }

    /**
     * @param $hash
     * @param $location
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function setTorrentLocation($hash, $location): false|string|null
    {
        return $this->postData('torrent_setLocation', ['hashes' => $hash, 'location' => $location]);
    }

    /**
     * 基本get方法
     * @param $endpoint
     * @param array $data
     * @return string|false|null
     * @throws ServerErrorException
     */
    public function getData($endpoint, array $data = []): string|null|false
    {
        $curl = $this->initCurl();
        $curl->setCookies($this->session_id);
        $config = $this->getConfig();
        $curl->get($this->clientUrl . $this->endpoints[$endpoint][$this->api_version], $data);

        if ($curl->error) {
            if ($config->is_debug) {
                var_dump($curl->request_headers);
                var_dump($curl->response_headers);
                var_dump($curl->response);
            }

            throw new ServerErrorException($curl->error_message);
        }

        return $curl->response;
    }

    /**
     * 基本post方法
     * @param string $endpoint
     * @param array|string $data
     * @param Curl|null $curl
     * @return false|string|null
     * @throws ServerErrorException
     */
    public function postData(string $endpoint, array|string $data, Curl $curl = null): false|string|null
    {
        $curl = $curl ?: $this->initCurl();
        $curl->setCookies($this->session_id);
        $config = $this->getConfig();
        $curl->post($this->clientUrl . $this->endpoints[$endpoint][$this->api_version], $data);

        if ($curl->error) {
            if ($config->is_debug) {
                var_dump($curl->request_headers);
                var_dump($curl->response_headers);
                var_dump($curl->response);
            }
            throw new ServerErrorException($curl->error_message);
        }

        return $curl->response;
    }

    /**
     * 拼接种子urls multipart/form-data
     * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation#add-new-torrent
     * @param array $param
     * @return string
     */
    public function buildData(array $param): string
    {
        $this->delimiter = str_replace('.', '', uniqid('--------------------', true));
        $eol = "\r\n";
        $data = '';
        // 拼接文件流
        foreach ($param as $name => $content) {
            $data .= "--" . $this->delimiter . $eol;
            $data .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
            $data .= $content . $eol;
        }
        $data .= "--" . $this->delimiter . "--" . $eol;
        return $data;
    }

    /**
     * 拼接种子上传文件流 multipart/form-data
     * https://github.com/qbittorrent/qBittorrent/wiki/Web-API-Documentation#add-new-torrent
     * @param array $param
     * @return string
     */
    public function buildTorrent(array $param): string
    {
        $this->delimiter = uniqid();
        $eol = "\r\n";
        $data = '';
        // 拼接文件流
        $data .= "--" . $this->delimiter . $eol;
        $data .= 'Content-Disposition: form-data; name="' . $param['name'] . '"; filename="' . $param['filename'] . '"' . $eol;
        $data .= 'Content-Type: application/x-bittorrent' . $eol . $eol;
        $data .= $param['torrents'] . $eol;
        unset($param['name']);
        unset($param['filename']);
        unset($param['torrents']);
        if (!empty($param)) {
            foreach ($param as $name => $content) {
                $data .= "--" . $this->delimiter . $eol;
                $data .= 'Content-Disposition: form-data; name="' . $name . '"' . $eol . $eol;
                $data .= $content . $eol;
            }
        }
        $data .= "--" . $this->delimiter . "--" . $eol;
        return $data;
    }

    /**
     * @return string
     * @throws ServerErrorException
     */
    public function status(): string
    {
        return $this->appVersion();
    }

    /**
     * 抽象方法，子类实现
     * @return array
     * @throws ServerErrorException|NotFoundException
     */
    public function getTorrentList(): array
    {
        $res = $this->getList();
        // 过滤，只保留正常做种
        $res = array_filter($res, function ($v) {
            if (isset($v['state']) && in_array($v['state'], array('uploading', 'stalledUP', 'pausedUP', 'queuedUP', 'checkingUP', 'forcedUP'))) {
                return true;
            }
            return false;
        }, ARRAY_FILTER_USE_BOTH);

        if (empty($res)) {
            throw new NotFoundException("从下载器未获取到做种数据" . PHP_EOL);
        }
        // 提取数组：hashString
        $info_hash = array_column($res, 'hash');
        // 升序排序
        sort($info_hash);
        $json = json_encode($info_hash, JSON_UNESCAPED_UNICODE);
        // 去重 应该从文件读入，防止重复提交
        $sha1 = sha1($json);
        // 组装返回数据
        $hashArray = [];
        $hashArray['hash'] = $json;
        $hashArray['sha1'] = $sha1;
        // 变换数组：hashString键名、目录为键值
        $hashArray['hashString'] = array_column($res, "save_path", 'hash');
        // 转移做种使用
        $hashArray[static::TORRENT_LIST] = array_column($res, null, 'hash');
        return $hashArray;
    }

    /**
     * 获取全部种子列表
     * @param array $data
     * @return array
     * @throws NotFoundException
     * @throws ServerErrorException
     * @link https://github.com/qbittorrent/qBittorrent/wiki/WebUI-API-(qBittorrent-4.1)#get-torrent-list
     */
    public function getList(array $data = []): array
    {
        $result = $this->getData('torrent_list', $data);
        $res = json_decode($result, true);
        if (empty($res)) {
            throw new NotFoundException("从下载器获取种子列表失败，可能qBittorrent暂时无响应，请稍后重试！" . PHP_EOL);
        }
        return $res;
    }

    /**
     * 抽象方法，子类实现
     * 解析结果
     * @param mixed $result
     * @return array
     */
    public function response(mixed $result): array
    {
        $rs = [
            'result' => 'success',      //success or fail
            'data' => [],
        ];
        if ($result === 'Ok.') {
            echo "********RPC添加下载任务成功 [{$result}]" . PHP_EOL . PHP_EOL;
        } else {
            $rs['result'] = empty($result) ? '未知错误，请稍后重试！' : $result;
            echo "-----RPC添加种子任务，失败 [" . $rs['result'] . "]" . PHP_EOL . PHP_EOL;
        }

        return $rs;
    }

    /**
     * 设置种子上传速度限制
     * @param string $hash 种子哈希值
     * @param int $speed 上传速度限制(字节/秒)，设置为0表示无限制
     * @return false|string|null 请求结果
     * @throws ServerErrorException 服务器错误时抛出异常
     */
    public function setUploadSpeedLimit($hash, $speed): false|string|null
    {
        $speed = $speed * 1024;
        return $this->postData('torrent_setUploadLimit', ['hashes' => $hash, 'limit' => $speed]);
    }

    /**
     * 设置种子下载速度限制
     * @param string $hash 种子哈希值
     * @param int $speed 下载速度限制(字节/秒)，设置为0表示无限制
     * @return false|string|null 请求结果
     * @throws ServerErrorException 服务器错误时抛出异常
     */
    public function setDownloadSpeedLimit($hash, $speed): false|string|null
    {
        $speed = $speed * 1024;
        return $this->postData('torrent_setDownloadLimit', ['hashes' => $hash, 'limit' => $speed]);
    }

    /**
     * 获取种子上传速度限制
     * @param string $hash 种子哈希值
     * @return int 上传速度限制(字节/秒)，如果没有限制返回0
     * @throws ServerErrorException
     */
    public function getUploadSpeedLimit($hash): int
    {
        $response = $this->postData('torrent_uploadLimit', ['hashes' => $hash]);
        if ($response) {
            $data = json_decode($response, true);
            return $data[$hash] / 1024 ?? 0;
        }
        return 0;
    }

    /**
     * 获取种子下载速度限制
     * @param string $hash 种子哈希值
     * @return int 下载速度限制(字节/秒)，如果没有限制返回0
     * @throws ServerErrorException
     */
    public function getDownloadSpeedLimit($hash): int
    {
        $response = $this->postData('torrent_downloadLimit', ['hashes' => $hash]);
        if ($response) {
            $data = json_decode($response, true);
            return $data[$hash] / 1024 ?? 0;
        }
        return 0;
    }

    public function start()
    {
        // TODO: Implement start() method.
    }

    public function stop()
    {
        // TODO: Implement stop() method.
    }
}