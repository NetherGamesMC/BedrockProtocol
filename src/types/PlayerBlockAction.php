<?php

/*
 * This file is part of BedrockProtocol.
 * Copyright (C) 2014-2022 PocketMine Team <https://github.com/pmmp/BedrockProtocol>
 *
 * BedrockProtocol is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

declare(strict_types=1);

namespace pocketmine\network\mcpe\protocol\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * This is used for PlayerAuthInput packet when the flags include PERFORM_BLOCK_ACTIONS
 */
final class PlayerBlockAction{
	public function __construct(
		private int $actionType,
		private BlockPosition $blockPosition,
		private int $face
	){
		if(!self::isValidActionType($actionType)){
			throw new \InvalidArgumentException("Invalid action type for " . self::class);
		}
	}

	/**
	 * Before 1.26.40, STOP_BREAK actions carried no block position or face. This creates such an action with a zero
	 * position and face.
	 */
	public static function stopBreak() : self{
		return new self(PlayerAction::STOP_BREAK, new BlockPosition(0, 0, 0), 0);
	}

	public function getActionType() : int{ return $this->actionType; }

	/**
	 * For STOP_BREAK actions decoded from protocols older than 1.26.40, this is always (0, 0, 0).
	 */
	public function getBlockPosition() : BlockPosition{ return $this->blockPosition; }

	/**
	 * For STOP_BREAK actions decoded from protocols older than 1.26.40, this is always 0.
	 */
	public function getFace() : int{ return $this->face; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$actionType = VarInt::readSignedInt($in);
		if(!self::isValidActionType($actionType)){
			//make sure we throw the correct exception type
			throw new PacketDecodeException("Invalid action type for " . self::class);
		}
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30 && $actionType === PlayerAction::STOP_BREAK){
			return self::stopBreak();
		}
		$blockPosition = CommonTypes::getBlockPosition($in);
		$face = VarInt::readSignedInt($in);
		return new self($actionType, $blockPosition, $face);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		VarInt::writeSignedInt($out, $this->actionType);
		if($protocolId <= ProtocolInfo::PROTOCOL_1_26_30 && $this->actionType === PlayerAction::STOP_BREAK){
			return;
		}
		CommonTypes::putBlockPosition($out, $this->blockPosition);
		VarInt::writeSignedInt($out, $this->face);
	}

	public static function isValidActionType(int $actionType) : bool{
		return match($actionType){
			PlayerAction::STOP_BREAK, //only has block info in 1.26.40+
			PlayerAction::ABORT_BREAK,
			PlayerAction::START_BREAK,
			PlayerAction::CRACK_BREAK,
			PlayerAction::PREDICT_DESTROY_BLOCK,
			PlayerAction::CONTINUE_DESTROY_BLOCK => true,
			default => false
		};
	}
}
