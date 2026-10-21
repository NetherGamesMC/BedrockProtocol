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

namespace pocketmine\network\mcpe\protocol\types\command\raw;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use function count;

final class CommandRawData{

	/**
	 * @param int[] $chainedSubCommandDataIndexes
	 * @param CommandOverloadRawData[] $overloads
	 * @phpstan-param list<int> $chainedSubCommandDataIndexes
	 * @phpstan-param list<CommandOverloadRawData> $overloads
	 */
	public function __construct(
		private string $name,
		private string $description,
		private int $flags,
		private string $permission,
		private int $aliasEnumIndex,
		private array $chainedSubCommandDataIndexes,
		private array $overloads,
	){}

	public function getName() : string{ return $this->name; }

	public function getDescription() : string{ return $this->description; }

	public function getFlags() : int{ return $this->flags; }

	public function getPermission() : string{ return $this->permission; }

	public function getAliasEnumIndex() : int{ return $this->aliasEnumIndex; }

	/**
	 * @return int[]
	 * @phpstan-return list<int>
	 */
	public function getChainedSubCommandDataIndexes() : array{ return $this->chainedSubCommandDataIndexes; }

	/**
	 * @return CommandOverloadRawData[]
	 * @phpstan-return list<CommandOverloadRawData>
	 */
	public function getOverloads() : array{ return $this->overloads; }

	public static function read(ByteBufferReader $in, int $protocolId) : self{
		$name = CommonTypes::getString($in);
		$description = CommonTypes::getString($in);
		$flags = LE::readUnsignedShort($in);
		$permission = $protocolId >= ProtocolInfo::PROTOCOL_1_21_130 ? CommonTypes::getString($in) : CommandPermissions::fromOrdinal(Byte::readUnsigned($in))->value;
		$aliasEnumIndex = LE::readSignedInt($in); //may be -1 for not set

		$chainedSubCommandDataIndexes = [];
		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_10){
			for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
				$chainedSubCommandDataIndexes[] = $protocolId >= ProtocolInfo::PROTOCOL_1_21_130 ? LE::readUnsignedInt($in) : LE::readUnsignedShort($in);
			}
		}

		$overloads = [];
		for($i = 0, $size = VarInt::readUnsignedInt($in); $i < $size; $i++){
			$overloads[] = CommandOverloadRawData::read($in, $protocolId);
		}

		return new self(
			name: $name,
			description: $description,
			flags: $flags,
			permission: $permission,
			aliasEnumIndex: $aliasEnumIndex,
			chainedSubCommandDataIndexes: $chainedSubCommandDataIndexes,
			overloads: $overloads
		);
	}

	public function write(ByteBufferWriter $out, int $protocolId) : void{
		CommonTypes::putString($out, $this->name);
		CommonTypes::putString($out, $this->description);
		LE::writeUnsignedShort($out, $this->flags);
		if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
			CommonTypes::putString($out, $this->permission);
		}else{
			Byte::writeUnsigned($out, (CommandPermissions::tryFrom($this->permission) ?? throw new \InvalidArgumentException("Invalid command permission \"$this->permission\""))->toOrdinal());
		}
		LE::writeSignedInt($out, $this->aliasEnumIndex);

		if($protocolId >= ProtocolInfo::PROTOCOL_1_20_10){
			VarInt::writeUnsignedInt($out, count($this->chainedSubCommandDataIndexes));
			foreach($this->chainedSubCommandDataIndexes as $index){
				if($protocolId >= ProtocolInfo::PROTOCOL_1_21_130){
					LE::writeUnsignedInt($out, $index);
				}else{
					LE::writeUnsignedShort($out, $index);
				}
			}
		}

		VarInt::writeUnsignedInt($out, count($this->overloads));
		foreach($this->overloads as $overload){
			$overload->write($out, $protocolId);
		}
	}
}
