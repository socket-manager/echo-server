<?php
/**
 * ステータスUNIT登録クラスのファイル
 * 
 * SocketManagerのsetProtocolUnitsメソッドへ引き渡されるクラスのファイル
 */

namespace App\ProtocolUnits;


use SocketManager\Library\IEntryUnits;
use SocketManager\Library\ProtocolQueueEnum;
use SocketManager\Library\SocketManagerParameter;

use App\ProtocolUnits\ProtocolEchoClientStatusEnum;


/**
 * プロトコルUNIT登録クラス
 * 
 * IEntryUnitsインタフェースをインプリメントする
 */
class ProtocolEchoClient implements IEntryUnits
{
    /**
     * @var const QUEUE_LIST キュー名のリスト
     */
    protected const QUEUE_LIST = [
        ProtocolQueueEnum::CONNECT->value,	// 接続を処理するキュー
        ProtocolQueueEnum::RECV->value,		// 受信処理のキュー
    ];

    /**
     * @var bool 送信フラグ
     */
    private bool $send = false;

    /**
     * コンストラクタ
     * 
     * @param bool $p_send 送信フラグ
     */
    public function __construct(bool $p_send = false)
    {
        $this->send = $p_send;
    }

    /**
     * キューリストの取得
     * 
     * @return array キュー名のリスト
     */
    public function getQueueList(): array
    {
        return (array)static::QUEUE_LIST;
    }

    /**
     * ステータスUNITリストの取得
     * 
     * @param string $p_que キュー名
     * @return array キュー名に対応するUNITリスト
     */
    public function getUnitList(string $p_que): array
    {
        $ret = [];

        if($p_que === ProtocolQueueEnum::CONNECT->value)
        {
            $ret[] = [
                'status' => ProtocolEchoClientStatusEnum::START->value,
                'unit' => $this->getConnectStart()
            ];
            $ret[] = [
                'status' => ProtocolEchoClientStatusEnum::SENDING->value,
                'unit' => $this->getConnectSending()
            ];
        }
        else
        if($p_que === ProtocolQueueEnum::RECV->value)
        {
            $ret[] = [
                'status' => ProtocolEchoClientStatusEnum::START->value,
                'unit' => $this->getRecvStart()
            ];
        }

        return $ret;
    }


    /**
     * 以降はステータスUNITの定義（"CONNECT"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：接続開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getConnectStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            if(!$this->send)
            {
                return null;
            }
            $dat = config('const.send_data', 'default data');
            $p_param->protocol()->setSendingData($dat);
            $fnc = $this->getConnectSending();
            $sta = $fnc($p_param);

            return $sta;
        };
    }

    /**
     * ステータス名： SENDING
     * 
     * 処理名：送信中
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getConnectSending()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $ret = $p_param->protocol()->sending();
            if($ret === true)
            {
                return null;
            }

            return ProtocolEchoClientStatusEnum::SENDING->value;
        };
    }


    /**
     * 以降はステータスUNITの定義（"RECV"キュー）
     */

    /**
     * ステータス名： START
     * 
     * 処理名：受信開始
     * 
     * @param SocketManagerParameter $p_param UNITパラメータ
     * @return ?string 遷移先のステータス名
     */
    protected function getRecvStart()
    {
        return function(SocketManagerParameter $p_param): ?string
        {
            $dat = '';
            $p_param->protocol()->recv($dat);
            $cid = $p_param->getConnectionId();
            $pid = getmypid();
            $p_param->logWriter('debug', ['receive data' => "payload[{$dat}] cid[{$cid}]pid[{$pid}]"]);

            return null;
        };
    }
}
