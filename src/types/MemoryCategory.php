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

/**
 * @see MemoryCategoryCounter
 */
final class MemoryCategory{
	public const UNKNOWN = 0;
	public const INVALID_SIZE_UNKNOWN = 1;
	public const ACTOR = 2;
	public const ACTOR_ANIMATION = 3;
	public const ACTOR_RENDERING = 4;
	public const BLOCK_TICKING_QUEUES = 5;
	public const BIOME_STORAGE = 6;
	public const BLOBS = 7;
	public const CEREAL = 8;
	public const CIRCUIT_SYSTEM = 9;
	public const CLIENT = 10;
	public const COMMANDS = 11;
	public const DB_STORAGE = 12;
	public const DEBUG = 13;
	public const DOCUMENTATION = 14;
	public const ECS_SYSTEMS = 15;
	public const FMOD = 16;
	public const FONTS = 17;
	public const IM_GUI = 18;
	public const INPUT = 19;
	public const JSON_UI = 20;
	public const JSON_UI_CONTROL_FACTORY_JSON = 21;
	public const JSON_UI_CONTROL_TREE = 22;
	public const JSON_UI_CONTROL_TREE_CONTROL_ELEMENT = 23;
	public const JSON_UI_CONTROL_TREE_POPULATE_DATA_BINDING = 24;
	public const JSON_UI_CONTROL_TREE_POPULATE_FOCUS = 25;
	public const JSON_UI_CONTROL_TREE_POPULATE_LAYOUT = 26;
	public const JSON_UI_CONTROL_TREE_POPULATE_OTHER = 27;
	public const JSON_UI_CONTROL_TREE_POPULATE_SPRITE = 28;
	public const JSON_UI_CONTROL_TREE_POPULATE_TEXT = 29;
	public const JSON_UI_CONTROL_TREE_POPULATE_TTS = 30;
	public const JSON_UI_CONTROL_TREE_VISIBILITY = 31;
	public const JSON_UI_CREATE_UI = 32;
	public const JSON_UI_DEFS = 33;
	public const JSON_UI_LAYOUT_MANAGER = 34;
	public const JSON_UI_LAYOUT_MANAGER_REMOVE_DEPENDENCIES = 35;
	public const JSON_UI_LAYOUT_MANAGER_INIT_VARIABLE = 36;
	public const LANGUAGES = 37;
	public const LEVEL = 38;
	public const LEVEL_STRUCTURES = 39;
	public const LEVEL_CHUNK = 40;
	public const LEVEL_CHUNK_GEN = 41;
	public const LEVEL_CHUNK_GEN_THREAD_LOCAL = 42;
	public const LIGHT_VOLUME_MANAGER = 43;
	public const NETWORK = 44;
	public const MARKETPLACE = 45;
	public const MATERIAL_DRAGON_COMPILED_DEFINITION = 46;
	public const MATERIAL_DRAGON_MATERIAL = 47;
	public const MATERIAL_DRAGON_RESOURCE = 48;
	public const MATERIAL_DRAGON_UNIFORM_MAP = 49;
	public const MATERIAL_RENDER_MATERIAL = 50;
	public const MATERIAL_RENDER_MATERIAL_GROUP = 51;
	public const MATERIAL_VARIATION_MANAGER = 52;
	public const MOLANG = 53;
	public const ORE_UI = 54;
	public const ORE_UI_CLIENT = 55;
	public const PERSONA_PIECES = 56;
	public const PERSONA_ANIMATIONS = 57;
	public const PERSONA_CHARACTERS = 58;
	public const PERSONA_SKIN_PACKS = 59;
	public const PERSONA_REPO = 60;
	public const PLAYER = 61;
	public const RENDER_CHUNK = 62;
	public const RENDER_CHUNK_INDEX_BUFFER = 63;
	public const RENDER_CHUNK_VERTEX_BUFFER = 64;
	public const RENDERING = 65;
	public const RENDERING_BGFX_INIT = 66;
	public const RENDERING_BGFX_START_FRAME = 67;
	public const RENDERING_BLOCK_TESSELLATOR = 68;
	public const RENDERING_END_FRAME = 69;
	public const RENDERING_GRAPHICS_TASKS_INIT = 70;
	public const RENDERING_LIBRARY = 71;
	public const RENDERING_POLYGON_OPERATOR_POOL = 72;
	public const RENDERING_PBR_TEXTURE_DATA = 73;
	public const RENDERING_RENDER_REGISTRY = 74;
	public const RENDERING_SETUP = 75;
	public const RENDERING_VERTICES = 76;
	public const REQUEST_LOG = 77;
	public const RESOURCE_PACKS = 78;
	public const SOUND = 79;
	public const SUB_CHUNK_BIOME_DATA = 80;
	public const SUB_CHUNK_BLOCK_DATA = 81;
	public const SUB_CHUNK_LIGHT_DATA = 82;
	public const TEXTURES = 83;
	public const WEATHER_RENDERER = 84;
	public const WORLD_GENERATOR = 85;
	public const TASKS = 86;
	public const TEST = 87;
	public const TEST_LOAD_TEST_TAGS = 88;
	public const SCRIPTING = 89;
	public const SCRIPTING_RUNTIME = 90;
	public const SCRIPTING_CONTEXT = 91;
	public const SCRIPTING_CONTEXT_BINDINGS_MC = 92;
	public const SCRIPTING_CONTEXT_BINDINGS_GT = 93;
	public const SCRIPTING_CONTEXT_RUN = 94;
	public const DATA_DRIVEN_UI = 95;
	public const DATA_DRIVEN_UI_DEFS = 96;
	public const GAMEFACE = 97;
	public const GAMEFACE_SYSTEM = 98;
	public const GAMEFACE_DOM = 99;
	public const GAMEFACE_CSS = 100;
	public const GAMEFACE_DISPLAY = 101;
	public const GAMEFACE_TEMP_ALLOCATOR = 102;
	public const GAMEFACE_POOL_ALLOCATOR = 103;
	public const GAMEFACE_DUMP = 104;
	public const GAMEFACE_MEDIA = 105;
	public const GAMEFACE_JSON = 106;
	public const GAMEFACE_SCRIPT_ENGINE = 107;
	public const GAMEFACE_SCRIPT = 108;
	public const GAMEFACE_LAYOUT = 109;
	public const EXECUTABLE = 110;
}
