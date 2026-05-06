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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

final class EnchantOption{
	/**
	 * @param Enchant[] $equipActivatedEnchantments
	 * @param Enchant[] $heldActivatedEnchantments
	 * @param Enchant[] $selfActivatedEnchantments
	 */
	public function __construct(
		private int $cost,
		private int $slotFlags,
		private array $equipActivatedEnchantments,
		private array $heldActivatedEnchantments,
		private array $selfActivatedEnchantments,
		private string $name,
		private int $optionId
	){}

	public function getCost() : int{ return $this->cost; }

	public function getSlotFlags() : int{ return $this->slotFlags; }

	/** @return Enchant[] */
	public function getEquipActivatedEnchantments() : array{ return $this->equipActivatedEnchantments; }

	/** @return Enchant[] */
	public function getHeldActivatedEnchantments() : array{ return $this->heldActivatedEnchantments; }

	/** @return Enchant[] */
	public function getSelfActivatedEnchantments() : array{ return $this->selfActivatedEnchantments; }

	public function getName() : string{ return $this->name; }

	public function getOptionId() : int{ return $this->optionId; }

	/**
	 * @return Enchant[]
	 */
	private static function readEnchantList(ByteBufferReader $in, int $protocolId) : array{
		$result = [];
		for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
			$result[] = Enchant::read($in, $protocolId);
		}
		return $result;
	}

	/**
	 * @param Enchant[] $list
	 */
	private static function writeEnchantList(ByteBufferWriter $out, array $list, int $protocolId) : void{
		VarInt::writeUnsignedInt($out, count($list));
		foreach($list as $item){
			$item->write($out, $protocolId);
		}
	}

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			$cost = Byte::readUnsigned($in);
		}else{
			$cost = VarInt::readUnsignedInt($in);
		}

		$slotFlags = LE::readUnsignedInt($in);
		$equipActivatedEnchants = self::readEnchantList($in, $protocolId);
		$heldActivatedEnchants = self::readEnchantList($in, $protocolId);
		$selfActivatedEnchants = self::readEnchantList($in, $protocolId);

		$name = CommonTypes::getString($in);
		$optionId = CommonTypes::readRecipeNetId($in);

		return new self($cost, $slotFlags, $equipActivatedEnchants, $heldActivatedEnchants, $selfActivatedEnchants, $name, $optionId);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		if($protocolId >= ProtocolInfo::PROTOCOL_1_26_20){
			Byte::writeUnsigned($out, $this->cost);
		}else{
			VarInt::writeUnsignedInt($out, $this->cost);
		}

		LE::writeUnsignedInt($out, $this->slotFlags);
		self::writeEnchantList($out, $this->equipActivatedEnchantments, $protocolId);
		self::writeEnchantList($out, $this->heldActivatedEnchantments, $protocolId);
		self::writeEnchantList($out, $this->selfActivatedEnchantments, $protocolId);

		CommonTypes::putString($out, $this->name);
		CommonTypes::writeRecipeNetId($out, $this->optionId);
	}
}
