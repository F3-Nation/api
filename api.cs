using System;
using System.Collections.Generic;
using System.Data;
using Npgsql;
using Microsoft.AspNetCore.Mvc;
using Newtonsoft.Json;

[ApiController]
[Route("api")]
public class ApiController : ControllerBase
{
    private const string ConnectionString = "Host=35.239.19.124;Database=f3_staging;Username=hatch;Password=6?Q/VYQa9gUQeZSx";
    private static readonly HashSet<string> ValidTables = new() { "locations", "orgs", "events" };

    [HttpGet]
    public IActionResult GetData([FromQuery] string table, [FromQuery] int limit = 10, [FromQuery] string sort = "asc", [FromQuery] string like_column = "", [FromQuery] string like_value = "")
    {
        if (!ValidTables.Contains(table))
        {
            return BadRequest(JsonConvert.SerializeObject(new { error = "Invalid table name" }));
        }

        if (sort.ToLower() != "asc" && sort.ToLower() != "desc")
        {
            return BadRequest(JsonConvert.SerializeObject(new { error = "Invalid sort parameter. Use 'asc' or 'desc'." }));
        }

        var query = $"SELECT * FROM {table}";
        var parameters = new List<NpgsqlParameter>();

        if (!string.IsNullOrEmpty(like_column) && !string.IsNullOrEmpty(like_value))
        {
            query += $" WHERE {like_column} LIKE @like_value";
            parameters.Add(new NpgsqlParameter("@like_value", $"%{like_value}%"));
        }

        query += " ORDER BY id " + sort + " LIMIT @limit";
        parameters.Add(new NpgsqlParameter("@limit", limit));

        try
        {
            using var conn = new NpgsqlConnection(ConnectionString);
            conn.Open();
            using var cmd = new NpgsqlCommand(query, conn);
            foreach (var param in parameters)
            {
                cmd.Parameters.Add(param);
            }
            using var reader = cmd.ExecuteReader();
            var results = new List<Dictionary<string, object>>();
            while (reader.Read())
            {
                var row = new Dictionary<string, object>();
                for (int i = 0; i < reader.FieldCount; i++)
                {
                    row[reader.GetName(i)] = reader.GetValue(i);
                }
                results.Add(row);
            }
            return Ok(JsonConvert.SerializeObject(results));
        }
        catch (Exception ex)
        {
            return StatusCode(500, JsonConvert.SerializeObject(new { error = ex.Message }));
        }
    }
}
