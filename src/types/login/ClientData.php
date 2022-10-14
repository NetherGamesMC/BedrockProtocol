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

namespace pocketmine\network\mcpe\protocol\types\login;

/**
 * Model class for LoginPacket JSON data for JsonMapper
 */
final class ClientData{

	/**
	 * @var ClientDataAnimationFrame[]
	 * >= PROTOCOL_1_13_0
	 */
	public array $AnimatedImageData = [];

	/** >= PROTOCOL_1_14_60 */
	public string $ArmSize;

	/** @required */
	public string $CapeData;

	/** >= PROTOCOL_1_13_0 */
	public string $CapeId;

	/** >= PROTOCOL_1_13_0 */
	public int $CapeImageHeight;

	/** >= PROTOCOL_1_13_0 */
	public int $CapeImageWidth;

	/** >= PROTOCOL_1_13_0 */
	public bool $CapeOnClassicSkin;

	/** @required */
	public int $ClientRandomId;

	/** @required */
	public int $CurrentInputMode;

	/** @required */
	public int $DefaultInputMode;

	/** @required */
	public string $DeviceId;

	/** @required */
	public string $DeviceModel;

	/** @required */
	public int $DeviceOS;

	/** @required */
	public string $GameVersion;

	/** @required */
	public int $GuiScale;

	/** >= PROTOCOL_1_19_10 */
	public bool $IsEditorMode;

	/** @required */
	public string $LanguageCode;

	/**
	 * @var ClientDataPersonaSkinPiece[]
	 * >= PROTOCOL_1_14_60
	 */
	public array $PersonaPieces;

	/** >= PROTOCOL_1_13_0 */
	public bool $PersonaSkin;

	/**
	 * @var ClientDataPersonaPieceTintColor[]
	 * >= PROTOCOL_1_14_60
	 */
	public array $PieceTintColors;

	/** @required */
	public string $PlatformOfflineId;

	/** @required */
	public string $PlatformOnlineId;

	public string $PlatformUserId = ""; //xbox-only, apparently

	/** >= PROTOCOL_1_16_210 */
	public string $PlayFabId;

	/** @required */
	public bool $PremiumSkin = false;

	/** @required */
	public string $SelfSignedId;

	/** @required */
	public string $ServerAddress;

	/** >= PROTOCOL_1_13_0 */
	public string $SkinAnimationData;

	/** >= PROTOCOL_1_14_60 */
	public string $SkinColor;

	/** @required */
	public string $SkinData;

	/** <= PROTOCOL_1_12_0 */
	public string $SkinGeometryName;

	/** <= PROTOCOL_1_12_0 */
	public string $SkinGeometry;

	/** >= PROTOCOL_1_13_0 */
	public string $SkinGeometryData;

	/** >= PROTOCOL_1_17_30 */
	public string $SkinGeometryDataEngineVersion;

	/** @required */
	public string $SkinId;

	/** >= PROTOCOL_1_13_0 */
	public int $SkinImageHeight;

	/** >= PROTOCOL_1_13_0 */
	public int $SkinImageWidth;

	/** >= PROTOCOL_1_13_0 */
	public string $SkinResourcePatch;

	/** @required */
	public string $ThirdPartyName;

	/** >= PROTOCOL_1_13_0 */
	public bool $ThirdPartyNameOnly;

	/** >= PROTOCOL_1_19_20 */
	public bool $TrustedSkin;

	/** @required */
	public int $UIProfile;
}
