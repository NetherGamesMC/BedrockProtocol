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

use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function array_map;
use function base64_decode;

final class ClientDataToSkinDataHelper{

	/**
	 * @throws \InvalidArgumentException
	 */
	private static function safeB64Decode(string $base64, string $context) : string{
		$result = base64_decode($base64, true);
		if($result === false){
			throw new \InvalidArgumentException("$context: Malformed base64, cannot be decoded");
		}
		return $result;
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function fromClientData(ClientData $clientData) : SkinData{
		/** @var SkinAnimation[] $animations */
		$animations = [];
		foreach($clientData->AnimatedImageData as $k => $animation){
			$animations[] = new SkinAnimation(
				new SkinImage(
					$animation->ImageHeight,
					$animation->ImageWidth,
					self::safeB64Decode($animation->Image, "AnimatedImageData.$k.Image")
				),
				$animation->Type,
				$animation->Frames,
				$animation->AnimationExpression ?? 0
			);
		}

		if(isset($clientData->SkinGeometryDataEngineVersion)){
			$geometryDataEngineVersion = self::safeB64Decode($clientData->SkinGeometryDataEngineVersion, "SkinGeometryDataEngineVersion"); //yes, they actually base64'd the version!
		}else{
			$geometryDataEngineVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
		}

		$skinData = self::safeB64Decode($clientData->SkinData, "SkinData");
		if(!isset($clientData->SkinImageHeight) || !isset($clientData->SkinImageWidth)) {
			$skinImage = SkinImage::fromLegacy($skinData);
		}else{
			$skinImage = new SkinImage($clientData->SkinImageHeight, $clientData->SkinImageWidth, $skinData);
		}

		$capeData = self::safeB64Decode($clientData->CapeData, "CapeData");
		if($capeData !== "") {
			if(!isset($clientData->CapeImageHeight) || !isset($clientData->CapeImageWidth)) {
				$capeImage = SkinImage::fromLegacy($capeData);
			}else{
				$capeImage = new SkinImage($clientData->CapeImageHeight, $clientData->CapeImageWidth, $capeData);
			}
		}

		return new SkinData(
			$clientData->SkinId,
			$clientData->PlayFabId ?? "",
			isset($clientData->SkinResourcePatch) ? self::safeB64Decode($clientData->SkinResourcePatch, "SkinResourcePatch") : null,
			$skinImage,
			$animations,
			$capeImage ?? new SkinImage(0, 0, ""),
			self::safeB64Decode($clientData->SkinGeometryData ?? $clientData->SkinGeometry, "SkinGeometryData"),
			$geometryDataEngineVersion,
			isset($clientData->SkinAnimationData) ? self::safeB64Decode($clientData->SkinAnimationData, "SkinAnimationData") : "",
			$clientData->CapeId ?? "",
			null,
			$clientData->ArmSize ?? "",
			$clientData->SkinColor ?? "",
			array_map(function(ClientDataPersonaSkinPiece $piece) : PersonaSkinPiece{
				return new PersonaSkinPiece($piece->PieceId, $piece->PieceType, $piece->PackId, $piece->IsDefault, $piece->ProductId);
			}, $clientData->PersonaPieces ?? []),
			array_map(function(ClientDataPersonaPieceTintColor $tint) : PersonaPieceTintColor{
				return new PersonaPieceTintColor($tint->PieceType, $tint->Colors);
			}, $clientData->PieceTintColors ?? []),
			true,
			$clientData->PremiumSkin,
			$clientData->PersonaSkin ?? false,
			$clientData->CapeOnClassicSkin ?? false,
			true, //assume this is true? there's no field for it ...
			$clientData->SkinGeometryName ?? null,
		);
	}
}
