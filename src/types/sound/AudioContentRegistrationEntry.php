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

namespace pocketmine\network\mcpe\protocol\types\sound;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

/**
 * The three content fields are SignedAudioContent in the spec: a compact JWT each.
 */
final class AudioContentRegistrationEntry{

	public function __construct(
		private readonly string $audioContentId,
		private readonly string $sharedMetadata,
		private readonly string $serverContent,
		private readonly string $playbackContent
	){
	}

	public function getAudioContentId() : string{
		return $this->audioContentId;
	}

	public function getSharedMetadata() : string{
		return $this->sharedMetadata;
	}

	public function getServerContent() : string{
		return $this->serverContent;
	}

	public function getPlaybackContent() : string{
		return $this->playbackContent;
	}

	public static function read(ByteBufferReader $in) : self{
		$audioContentId = CommonTypes::getString($in);
		$sharedMetadata = CommonTypes::getString($in);
		$serverContent = CommonTypes::getString($in);
		$playbackContent = CommonTypes::getString($in);

		return new self($audioContentId, $sharedMetadata, $serverContent, $playbackContent);
	}

	public function write(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->audioContentId);
		CommonTypes::putString($out, $this->sharedMetadata);
		CommonTypes::putString($out, $this->serverContent);
		CommonTypes::putString($out, $this->playbackContent);
	}
}
