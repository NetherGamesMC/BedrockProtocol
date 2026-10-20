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

namespace pocketmine\network\mcpe\protocol\types\login\clientdata;

use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPieceType;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinArmSizeType;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use Ramsey\Uuid\Uuid;
use function array_map;
use function array_pad;
use function array_slice;
use function array_values;
use function base64_decode;
use function hexdec;
use function is_int;
use function preg_match;

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
	 * Parses a hex color string in #AARRGGBB or shorter formats into a Color.
	 * Older clients are known to send empty or otherwise malformed colours (e.g. for default skins), so this falls back
	 * to a transparent colour instead of rejecting the login.
	 */
	private static function parseColorString(string $colorString) : Color{
		if(preg_match('/^#([a-fA-F0-9]{1,8})$/', $colorString, $matches) === 1 && is_int($argb = hexdec($matches[1]))){
			return Color::fromARGB($argb);
		}

		return new Color(0, 0, 0, 0);
	}

	private static function parsePersonaSkinPieceType(string $pieceType) : PersonaSkinPieceType{
		return PersonaSkinPieceType::tryFromJsonString($pieceType) ?? PersonaSkinPieceType::UNKNOWN;
	}

	private static function parsePersonaSkinPiece(ClientDataPersonaSkinPiece $piece) : PersonaSkinPiece{
		return new PersonaSkinPiece(
			$piece->PieceId,
			self::parsePersonaSkinPieceType($piece->PieceType),
			Uuid::isValid($piece->PackId) ? Uuid::fromString($piece->PackId) : Uuid::fromString(Uuid::NIL),
			$piece->IsDefault,
			$piece->ProductId
		);
	}

	private static function parsePersonaPieceTintColor(ClientDataPersonaPieceTintColor $tint) : PersonaPieceTintColor{
		$colors = array_map(self::parseColorString(...), array_values($tint->Colors));
		//older clients don't always send exactly 4 colours; pad with transparent / truncate rather than failing the login
		/** @phpstan-var array{Color, Color, Color, Color} $colors */
		$colors = array_slice(array_pad($colors, PersonaPieceTintColor::EXPECTED_COLOR_COUNT, new Color(0, 0, 0, 0)), 0, PersonaPieceTintColor::EXPECTED_COLOR_COUNT);

		return new PersonaPieceTintColor(
			self::parsePersonaSkinPieceType($tint->PieceType),
			$colors
		);
	}

	/**
	 * @throws \InvalidArgumentException
	 */
	public static function fromClientData(ClientData $clientData) : SkinData{
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
				$animation->AnimationExpression
			);
		}

		return new SkinData(
			$clientData->SkinId,
			$clientData->PlayFabId ?? "",
			self::safeB64Decode($clientData->SkinResourcePatch, "SkinResourcePatch"),
			new SkinImage($clientData->SkinImageHeight, $clientData->SkinImageWidth, self::safeB64Decode($clientData->SkinData, "SkinData")),
			$animations,
			new SkinImage($clientData->CapeImageHeight, $clientData->CapeImageWidth, self::safeB64Decode($clientData->CapeData, "CapeData")),
			self::safeB64Decode($clientData->SkinGeometryData, "SkinGeometryData"),
			self::safeB64Decode($clientData->SkinGeometryDataEngineVersion, "SkinGeometryDataEngineVersion"), //yes, they actually base64'd the version!
			self::safeB64Decode($clientData->SkinAnimationData, "SkinAnimationData"),
			$clientData->CapeId,
			null,
			SkinArmSizeType::tryFrom($clientData->ArmSize) ?? SkinArmSizeType::WIDE,
			self::parseColorString($clientData->SkinColor),
			array_values(array_map(self::parsePersonaSkinPiece(...), $clientData->PersonaPieces)),
			array_values(array_map(self::parsePersonaPieceTintColor(...), $clientData->PieceTintColors)),
			SkinData::TRUSTED_SKIN_TRUE, //TrustedSkin is Mojang's own content filter flag, we always trust skins
			$clientData->PremiumSkin,
			$clientData->PersonaSkin,
			$clientData->CapeOnClassicSkin,
			true, //assume this is true? there's no field for it ...
			$clientData->OverrideSkin ?? true,
			$clientData->ProfileHash,
		);
	}
}
